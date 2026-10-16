<?php

use Tygh\Tygh;

$cart = &Tygh::$app['session']['cart'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'buy_now') {
        $product_id = $_REQUEST['product_id'] ?? 0;

        if ($product_id) {
            fn_clear_cart($cart);

            $product_data = [
                $product_id => [
                    'product_id' => $product_id,
                    'amount' => 1,
                ]
            ];
            
            fn_add_product_to_cart($product_data, $cart, $auth);
            fn_calculate_cart_content($cart, $auth, 'E', true, 'F', true);
            fn_save_cart_content($cart, $auth['user_id']);

            return [CONTROLLER_STATUS_REDIRECT, 'checkout.checkout'];
        }
    }

    return [CONTROLLER_STATUS_OK];
}
