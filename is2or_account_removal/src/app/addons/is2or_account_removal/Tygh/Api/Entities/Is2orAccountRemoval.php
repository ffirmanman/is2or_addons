<?php

namespace Tygh\Api\Entities;

use Tygh\Enum\UserTypes;
use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;
use Tygh\Api\Entities\AuthTokens;

class Is2orAccountRemoval extends AuthTokens
{
    /**
     * GET /is2or_account_removal
     */
    public function index($id = '', $params = [])
    {
        return ['status' => Response::STATUS_METHOD_NOT_ALLOWED];
    }

    /**
     * POST /is2or_account_removal
     */
    public function create($params)
    {
        $result = false;
        $user_info_detail = fn_get_user_info($this->auth['user_id']);

        if (UserTypes::isCustomer($this->auth['user_type']) && $user_info_detail['status'] == 'A') {
            fn_change_user_status($this->auth['user_id'], 'D', false);
            $comment = isset($params['comment']) ? $params['comment'] : '';
            $user_info = fn_get_user_short_info($this->auth['user_id']);
            $result = fn_send_anonymization_request_email($user_info, $comment);
        } elseif (UserTypes::isCustomer($this->auth['user_type']) && $user_info_detail['status'] == 'D') {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => [
                    'message' => 'User accounts already deleted',
                ]
            ];
        } else {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => [
                    'message' => 'Administrator accounts cannot be deleted',
                ]
            ];
        }

        $this->logoutUser($params);

        $mail_message = 'Success send email notification';
        if (!$result) {
            $mail_message = __(
                'user_action_request_fail',
                [
                    '[email]' => Registry::get('settings.Company.company_users_department')
                ]
            );
        }

        return [
            'status' => Response::STATUS_OK,
            'data' => [
                'message' => 'Your account has been successfully removed.',
                'mail_message' => $mail_message,
            ]
        ];
    }

    public function update($id = '', $params = [])
    {
        return ['status' => Response::STATUS_METHOD_NOT_ALLOWED];
    }

    public function delete($id = '', $params = [])
    {
        return ['status' => Response::STATUS_METHOD_NOT_ALLOWED];
    }

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

     private function logoutUser(array $params)
    {
        if (empty($params['ekey'])) {
            return;
        }
        fn_delete_ekey($params['ekey']);
    }

}