<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;
use Tygh\Tygh;

class Is2orBuyNow extends AEntity
{
    /**
     * POST /is2or_order_now
     * Handle "Buy Now" logic: Clear cart, add one specific product, redirect ready.
     */
    public function create($params)
    {
        $auth = &Tygh::$app['session']['auth'];
        $cart = &Tygh::$app['session']['cart'];
        
        $user_id = (int) $this->auth['user_id'];
        
        if (empty($params['product_id'])) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Product ID is required']
            ];
        }

        $product_id = (int) $params['product_id'];
        $amount = isset($params['amount']) ? (int) $params['amount'] : 1;
        
        $product_options = isset($params['product_options']) ? (array) $params['product_options'] : [];

        fn_clear_cart($cart);

        $product_data = [
            $product_id => [
                'product_id'      => $product_id,
                'amount'          => $amount,
                'product_options' => $product_options,
            ]
        ];

        $result_ids = fn_add_product_to_cart($product_data, $cart, $auth);

        if ($result_ids) {
            fn_calculate_cart_content($cart, $auth, 'C', true, 'F', true);
            
            fn_save_cart_content($cart, $user_id);
            
            $user_data = fn_get_user_info($user_id);
            $cartNew = &Tygh::$app['session']['cart'];

            return [
                'status' => Response::STATUS_OK,
                'data'   => [
                    'message' => 'Product successfully added for Buy Now',
                    'cart' => $cartNew,
                    'user_data' => $user_data
                ]
            ];
        }

        return [
            'status' => Response::STATUS_BAD_REQUEST,
            'data'   => ['message' => 'Failed to add product to cart (Out of stock or invalid options)']
        ];
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
