<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;

/**
 * API Endpoint: POST /api/is2or_google_login
 *
 */
class Is2orGoogleLogin extends AEntity
{
    const TOKEN_TTL            = 3600;
    const GOOGLE_TOKEN_INFO_URL = 'https://oauth2.googleapis.com/tokeninfo';
    const PLATFORM_ANDROID     = 'android';
    const PLATFORM_IOS         = 'ios';

    // ----------------------------------------------------------------
    // Disabled methods
    // ----------------------------------------------------------------

    public function index($id = '', $params = [])
    {
        return ['status' => Response::STATUS_METHOD_NOT_ALLOWED];
    }

    public function update($id = '', $params = [])
    {
        return ['status' => Response::STATUS_METHOD_NOT_ALLOWED];
    }

    public function delete($id = '', $params = [])
    {
        return ['status' => Response::STATUS_METHOD_NOT_ALLOWED];
    }

    // ----------------------------------------------------------------
    // POST /api/is2or_google_login
    // ----------------------------------------------------------------

    public function create($params)
    {
        // 1. Validate required input
        if (empty($params['id_token'])) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Missing required parameter: id_token'],
            ];
        }

        if (empty($params['device_id'])) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Missing required parameter: device_id'],
            ];
        }

        if (empty($params['platform'])) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Missing required parameter: platform (android or ios)'],
            ];
        }

        $platform = strtolower(trim($params['platform']));

        if (!in_array($platform, [self::PLATFORM_ANDROID, self::PLATFORM_IOS])) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Invalid platform value. Allowed: android, ios'],
            ];
        }

        // 2. Resolve expected Client ID based on platform
        $expected_client_id = $this->getClientIdByPlatform($platform);

        if (empty($expected_client_id)) {
            return [
                'status' => Response::STATUS_INTERNAL_SERVER_ERROR,
                'data'   => ['message' => 'Google Client ID for platform "' . $platform . '" is not configured'],
            ];
        }

        // 3. Verify the Google id_token
        $google_user = $this->verifyGoogleToken($params['id_token']);

        if ($google_user === false) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => ['message' => 'Invalid or expired Google id_token'],
            ];
        }

        // 4. Validate audience matches the platform Client ID
        if ($google_user['aud'] !== $expected_client_id) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => ['message' => 'Google id_token audience does not match the ' . $platform . ' Client ID'],
            ];
        }

        // 5. Find or create CS-Cart user
        $user_id = $this->findOrCreateUser($google_user);

        if (!$user_id) {
            return [
                'status' => Response::STATUS_INTERNAL_SERVER_ERROR,
                'data'   => ['message' => 'Failed to create or retrieve user account'],
            ];
        }

        // 6. Issue CS-Cart API token
        $token_data = $this->issueApiToken($user_id);

        if (empty($token_data)) {
            return [
                'status' => Response::STATUS_INTERNAL_SERVER_ERROR,
                'data'   => ['message' => 'Failed to generate authentication token'],
            ];
        }

        // 7. Return response
        return [
            'status' => Response::STATUS_OK,
            'data'   => [
                'token'      => $token_data['token'],
                'ttl'        => $token_data['ttl'],
                'profile_id' => (int) $token_data['profile_id'],
                'user_id'    => (int) $user_id,
            ],
        ];
    }

    // ----------------------------------------------------------------
    // Privileges
    // ----------------------------------------------------------------

    public function privileges()
    {
        return [
            'index'  => false,
            'create' => true,
            'update' => false,
            'delete' => false,
        ];
    }

    public function privilegesCustomer()
    {
        return [
            'index'  => false,
            'create' => true,
            'update' => false,
            'delete' => false,
        ];
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    /**
     * Get the configured Google Client ID for the given platform.
     *
     * @param  string $platform
     * @return string
     */
    private function getClientIdByPlatform($platform)
    {
        if ($platform === self::PLATFORM_ANDROID) {
            return (string) Registry::get('addons.is2or_google_login.google_client_id_android');
        }

        if ($platform === self::PLATFORM_IOS) {
            return (string) Registry::get('addons.is2or_google_login.google_client_id_ios');
        }

        return '';
    }

    /**
     * Verify the Google id_token via Google's tokeninfo endpoint.
     *
     * @param  string $id_token
     * @return array|false
     */
    private function verifyGoogleToken($id_token)
    {
        $url = self::GOOGLE_TOKEN_INFO_URL . '?id_token=' . urlencode($id_token);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || empty($response)) {
            return false;
        }

        $data = json_decode($response, true);

        if (
            empty($data['email'])
            || empty($data['email_verified'])
            || $data['email_verified'] !== 'true'
        ) {
            return false;
        }

        return $data;
    }

    /**
     * Find an existing CS-Cart user by e-mail, or create a new customer account.
     *
     * @param  array $google_user
     * @return int|false
     */
    private function findOrCreateUser(array $google_user)
    {
        $email = mb_strtolower(trim($google_user['email']));

        $existing = db_get_row(
            "SELECT user_id FROM ?:users WHERE email = ?s AND user_type = 'C' LIMIT 1",
            $email
        );

        if (!empty($existing['user_id'])) {
            $status = db_get_field(
                "SELECT status FROM ?:users WHERE user_id = ?i",
                $existing['user_id']
            );

            if ($status === 'D') {
                db_query(
                    "UPDATE ?:users SET status = 'A' WHERE user_id = ?i",
                    $existing['user_id']
                );
            }

            return (int) $existing['user_id'];
        }

        // Build name parts from Google profile
        $raw_name   = isset($google_user['name']) ? $google_user['name'] : $email;
        $name_parts = explode(' ', $raw_name, 2);

        $firstname = preg_replace("/[^\p{L}'\-\s]/u", '', $name_parts[0]);
        $lastname  = isset($name_parts[1]) ? preg_replace("/[^\p{L}'\-\s]/u", '', $name_parts[1]) : '';

        $firstname = trim($firstname) ?: 'User';
        $lastname  = trim($lastname);

        $user_data = [
            'email'     => $email,
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'user_type' => 'C',
            'status'    => 'A',
            'password1' => $this->generateRandomPassword(),
            'password2' => $this->generateRandomPassword(),
        ];

        // $auth must be initialized — fn_update_user passes it by reference
        $auth = [];
        list($user_id,) = fn_update_user(0, $user_data, $auth, true, true);

        return $user_id ? (int) $user_id : false;
    }

    /**
     * Issue a CS-Cart API authentication token.
     * Uses fn_create_api_token() — the correct CS-Cart mechanism.
     *
     * @param  int $user_id
     * @return array|false  ['token' => string, 'profile_id' => int]
     */
    private function issueApiToken($user_id)
    {
        $user_info = fn_get_user_info($user_id);

        if (empty($user_info)) {
            return false;
        }

        $profile_id = db_get_field(
            "SELECT profile_id FROM ?:user_profiles WHERE user_id = ?i LIMIT 1",
            $user_id
        );

        list($token, $expiry_time) = fn_get_user_auth_token($user_id);

        if (empty($token)) {
            return false;
        }

        return [
            'token'      => $token,
            'ttl'        => $expiry_time - TIME,
            'profile_id' => (int) $profile_id,
        ];
    }

    /**
     * Generate a cryptographically random password.
     *
     * @return string
     */
    private function generateRandomPassword()
    {
        return bin2hex(random_bytes(16));
    }
}
