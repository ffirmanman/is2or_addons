<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;

class Is2orShipmentFee extends AEntity
{
    /**
     * POST /is2or_shipment_fee
     * Calculate available shipping methods and rates for current cart
     */
    public function create($params)
    {
        $user_id = (int) $this->auth['user_id'];
        if (empty($user_id)) {
            return ['status' => Response::STATUS_UNAUTHORIZED];
        }

        if (!Registry::get('addons.tryoto_shipping')) {
            \fn_load_addon('tryoto_shipping');
        }
        if (!Registry::get('addons.torod_shipping')) {
            \fn_load_addon('torod_shipping');
        }

        $user_data = \fn_get_user_info($user_id);
        if (empty($user_data)) {
            return ['status' => Response::STATUS_NOT_FOUND, 'data' => ['message' => 'User not found']];
        }

        $customer_auth = \fn_fill_auth($user_data);

        $cart = [
            'user_data' => $user_data,
            'products'  => \fn_get_cart_products($user_id),
            'shipping_required' => true
        ];

        if (empty($cart['products'])) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Cart is empty']
            ];
        }

        // 4. Update lokasi pengiriman berdasarkan input API
        $cart['user_data']['s_country'] = !empty($params['s_country']) ? $params['s_country'] : 'SA';
        $cart['user_data']['s_city']    = !empty($params['s_city']) ? $params['s_city'] : '';
        $cart['user_data']['s_state']   = !empty($params['s_state']) ? $params['s_state'] : '';
        $cart['user_data']['s_zipcode'] = !empty($params['s_zipcode']) ? $params['s_zipcode'] : '';

        try {
            list($cart_products, $product_groups) = \fn_calculate_cart_content($cart, $customer_auth, 'S', true, 'F');

            $final_shipping = [];

            // Loop through all product groups (to anticipate multi-vendor)
            foreach ($product_groups as $group) {
                if (!empty($group['shippings'])) {
                    foreach ($group['shippings'] as $shp) {
                        $final_shipping[] = [
                            'shipping_id' => (int) $shp['shipping_id'],
                            'label'       => trim($shp['shipping']),
                            'amount'      => (float) $shp['rate'],
                            'detail'      => !empty($shp['delivery_time']) ? trim($shp['delivery_time']) : '',
                        ];
                    }
                }
            }

            return [
                'status' => Response::STATUS_OK,
                'data'   => ['shippings' => $final_shipping]
            ];

        } catch (\Exception $e) {
            return [
                'status' => Response::STATUS_INTERNAL_SERVER_ERROR,
                'data'   => ['message' => $e->getMessage()]
            ];
        }
    }

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

    public function privileges()
    {
        return ['create' => true];
    }

    public function privilegesCustomer()
    {
        return ['create' => true];
    }
}
