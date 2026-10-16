<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Tygh;

class Is2orApiAddress extends AEntity
{
    /**
     * GET /api/is2or_api_address
     * GET /api/is2or_api_address/{id}
     *
     * If $id is empty -> return list of all addresses.
     * If $id is provided -> return details of a single address (with ownership check).
     */
    public function index($id = '', $params = array())
    {
        $user_id = $this->auth['user_id'];
        $cart = !empty(Tygh::$app['session']['cart']) && is_array(Tygh::$app['session']['cart'])
            ? Tygh::$app['session']['cart']
            : [];

        // --- GET single address ---
        if (!empty($id)) {
            $profile_id = (int) $id;

            if (!fn_is2or_address_selector_is_owned_profile($user_id, $profile_id)) {
                return array(
                    'status' => Response::STATUS_FORBIDDEN,
                    'data'   => array('message' => __('access_denied')),
                );
            }

            $user_data = fn_get_user_info($user_id, true, $profile_id);

            if (empty($user_data)) {
                return array(
                    'status' => Response::STATUS_NOT_FOUND,
                    'data'   => array('message' => __('text_not_found', ['[item]' => __('address')])),
                );
            }

            return array(
                'status' => Response::STATUS_OK,
                'data'   => array(
                    'profile'                            => $user_data,
                    'address_selector_active_profile_id' => fn_is2or_address_selector_get_active_profile_id($this->auth, $cart),
                ),
            );
        }

        // --- GET list (existing logic) ---
        $user_info_detail = fn_get_user_info($user_id);

        [$profiles,] = fn_is2or_address_selector_get_profiles($this->auth);

        return [
            'status' => Response::STATUS_OK,
            'data' => [
                'address_selector_profiles'          => $profiles,
                'address_selector_active_profile_id' => fn_is2or_address_selector_get_active_profile_id($this->auth, $cart),
            ]
        ];
    }

    /**
     * POST /api/is2or_api_address
     * Create a new address/profile.
     *
     * Body parameters follow the structure of $user_data in the CS-Cart profile form,
     * example: b_firstname, b_lastname, b_address, b_city, b_country, b_state,
     * b_zipcode, b_phone, s_firstname, ..., ship_to_another (bool), profile_name, etc.
     */
    public function create($params)
    {
        $user_id = $this->auth['user_id'];

        $user_data = isset($params['user_data']) ? (array) $params['user_data'] : [];
        $user_data['profile_id'] = 0;

        $ship_to_another = !empty($params['ship_to_another']);
        $profile_fields = fn_get_profile_fields();

        fn_convert_profile_dates_to_timestamps($user_data, $profile_fields);

        if (!$ship_to_another) {
            fn_fill_address($user_data, $profile_fields);
        }

        $saved_profile_id = fn_update_user_profile($user_id, $user_data, '', $ship_to_another);

        if (!$saved_profile_id) {
            return array(
                'status' => Response::STATUS_INTERNAL_ERROR,
                'data'   => array('message' => __('error_occurred')),
            );
        }

        $cart = !empty(Tygh::$app['session']['cart']) && is_array(Tygh::$app['session']['cart'])
            ? Tygh::$app['session']['cart']
            : [];

        $created_user_data = fn_get_user_info($user_id, true, $saved_profile_id);

        return array(
            'status' => Response::STATUS_OK,
            'data'   => array(
                'profile'                             => $created_user_data,
                'address_selector_active_profile_id'  => fn_is2or_address_selector_get_active_profile_id($this->auth, $cart),
            ),
        );
    }

    /**
     * PUT /api/is2or_api_address/{id}
     * Update an existing address/profile.
     *
     * To set an active profile, send body: { "action": "set_active" }
     * (see the activateProfile method below for detailed behavior).
     */
    public function update($id, $params)
    {
        $user_id = $this->auth['user_id'];
        $profile_id = (int) $id;

        if (!fn_is2or_address_selector_is_owned_profile($user_id, $profile_id)) {
            return array(
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => array('message' => __('access_denied')),
            );
        }

        $action = !empty($params['action']) ? $params['action'] : null;
        if ($action === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded['action'])) {
                $action = $decoded['action'];
            }
        }

        if ($action === 'set_active') {
            return $this->activateProfile($profile_id);
        }

        $user_data = isset($params['user_data']) ? (array) $params['user_data'] : [];
        $user_data['profile_id'] = $profile_id;

        $ship_to_another = !empty($params['ship_to_another']);
        $profile_fields = fn_get_profile_fields();

        fn_convert_profile_dates_to_timestamps($user_data, $profile_fields);

        if (!$ship_to_another) {
            fn_fill_address($user_data, $profile_fields);
        }

        $saved_profile_id = fn_update_user_profile($user_id, $user_data, '', $ship_to_another);

        if (!$saved_profile_id) {
            return array(
                'status' => Response::STATUS_INTERNAL_ERROR,
                'data'   => array('message' => __('error_occurred')),
            );
        }

        $cart = !empty(Tygh::$app['session']['cart']) && is_array(Tygh::$app['session']['cart'])
            ? Tygh::$app['session']['cart']
            : [];

        $active_profile_id = fn_is2or_address_selector_get_active_profile_id($this->auth, $cart);
        if ((int) $active_profile_id === (int) $saved_profile_id) {
            fn_is2or_address_selector_activate_profile($this->auth, $cart, $saved_profile_id);
        }

        $updated_user_data = fn_get_user_info($user_id, true, $saved_profile_id);

        return array(
            'status' => Response::STATUS_OK,
            'data'   => array(
                'profile'                             => $updated_user_data,
                'address_selector_active_profile_id'  => fn_is2or_address_selector_get_active_profile_id($this->auth, $cart),
            ),
        );
    }

    /**
     * DELETE /api/is2or_api_address/{id}
     */
    public function delete($id)
    {
        $user_id = $this->auth['user_id'];
        $profile_id = (int) $id;

        if (!fn_is2or_address_selector_is_owned_profile($user_id, $profile_id)) {
            return array(
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => array('message' => __('access_denied')),
            );
        }

        $cart = !empty(Tygh::$app['session']['cart']) && is_array(Tygh::$app['session']['cart'])
            ? Tygh::$app['session']['cart']
            : [];

        $active_profile_id = fn_is2or_address_selector_get_active_profile_id($this->auth, $cart);

        if (!fn_delete_user_profile($user_id, $profile_id)) {
            return array(
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => array('message' => __('is2or_address_selector.cannot_delete_primary')),
            );
        }

        // If the deleted profile was the active profile, fall back to the primary profile.
        if ((int) $active_profile_id === (int) $profile_id) {
            $fallback_profile_id = fn_is2or_address_selector_get_primary_profile_id($user_id);
            if ($fallback_profile_id) {
                fn_is2or_address_selector_activate_profile($this->auth, $cart, $fallback_profile_id);
            }
        }

        return array(
            'status' => Response::STATUS_OK,
            'data'   => array(
                'message'                            => __('is2or_address_selector.address_deleted'),
                'address_selector_active_profile_id'   => fn_is2or_address_selector_get_active_profile_id($this->auth, $cart),
            ),
        );
    }

    /**
     * Set a specific profile as the active profile for the current cart/checkout session.
     * Called from update() when the request body contains {"action": "set_active"}.
     *
     * Follows the $process_profile_selection logic from the reference controller.
     */
    protected function activateProfile($profile_id)
    {
        $user_id = $this->auth['user_id'];

        if (!isset(Tygh::$app['session']['cart']) || !is_array(Tygh::$app['session']['cart'])) {
            Tygh::$app['session']['cart'] = [];
        }

        $cart = &Tygh::$app['session']['cart'];

        if (!fn_is2or_address_selector_is_owned_profile($user_id, $profile_id)) {
            return array(
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => array('message' => __('access_denied')),
            );
        }

        [, $selectable_profiles] = fn_is2or_address_selector_get_profiles($this->auth);

        if (empty($selectable_profiles[$profile_id])) {
            return array(
                'status' => Response::STATUS_UNPROCESSABLE_ENTITY,
                'data'   => array(
                    'message'    => __('is2or_address_selector.complete_address_to_select'),
                    'profile_id' => $profile_id,
                ),
            );
        }

        if (!fn_is2or_address_selector_activate_profile($this->auth, $cart, $profile_id)) {
            return array(
                'status' => Response::STATUS_INTERNAL_ERROR,
                'data'   => array('message' => __('error_occurred')),
            );
        }

        if (function_exists('fn_save_cart_content')) {
            fn_save_cart_content($cart, $user_id);
        }

        return array(
            'status' => Response::STATUS_OK,
            'data'   => array(
                'message'                             => __('is2or_address_selector.delivery_address_selected'),
                'address_selector_active_profile_id'  => fn_is2or_address_selector_get_active_profile_id($this->auth, $cart),
            ),
        );
    }

    /**
     * @param  string $method_name
     * @return bool
     */
    public function isAccessable($method_name)
    {
        return in_array($method_name, array('index', 'create', 'update', 'delete'), true);
    }

    public function privileges()
    {
        return array(
            'create' => true,
            'update' => true,
            'delete' => true,
            'index'  => true,
        );
    }

    public function privilegesCustomer()
    {
        return array(
            'index'  => true,
            'create' => true,
            'update' => true,
            'delete' => true,
        );
    }
}