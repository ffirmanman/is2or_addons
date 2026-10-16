<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

$session_cart = Tygh::$app['session']['cart'];

if (!empty($session_cart['products'])) {

    $cart_product_ids = [];
    foreach ($session_cart['products'] as $cart_item) {
        if (!empty($cart_item['product_id'])) {
            $cart_product_ids[] = (int) $cart_item['product_id'];
        }
    }
    $cart_product_ids = array_unique($cart_product_ids);

    if (!empty($cart_product_ids)) {
        $first_product_id = (int) reset($cart_product_ids);
        $also_bought_ids = fn_is2or_ai_integration_also_bought_products_view($first_product_id, []);

        if (!empty($also_bought_ids)) {
            $also_bought_products = fn_is2or_ai_integration_get_products_by_ids($also_bought_ids);

            if (!empty($also_bought_products)) {
                Tygh::$app['view']->assign('is2or_also_bought_products', $also_bought_products);
            }
        }
    }
}