<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'view') {
    $req_product_id = !empty($product_data['product_id'])
        ? (int) $product_data['product_id']
        : (!empty($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0);

    $similar_ids = fn_is2or_ai_integration_similar_product_view($req_product_id, $product_data);

    if (!empty($similar_ids)) {
        $similar_products = fn_is2or_ai_integration_get_products_by_ids($similar_ids);

        if (!empty($similar_products)) {
            Tygh::$app['view']->assign('is2or_similar_products', $similar_products);
        }
    }

    
    $also_bought_ids = fn_is2or_ai_integration_also_bought_products_view($req_product_id, $product_data);

    if (!empty($also_bought_ids)) {
        $also_bought_products = fn_is2or_ai_integration_get_products_by_ids($also_bought_ids);

        if (!empty($also_bought_products)) {
            Tygh::$app['view']->assign('is2or_also_bought_products', $also_bought_products);
        }
    }
}