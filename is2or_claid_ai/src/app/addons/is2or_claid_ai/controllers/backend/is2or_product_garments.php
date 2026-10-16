<?php

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $garment_id = $_REQUEST['garment_id'] ?? 0;
        $garment_data = $_REQUEST['garment_data'] ?? [];

        if (!empty($garment_data)) {
            fn_is2or_claid_ai_update_garment($garment_id, $garment_data);

            return [CONTROLLER_STATUS_OK, 'products.update?product_id=' . $garment_data['product_id'] . '&selected_section=is2or_claid_tryon'];
        }
    }

    if ($mode == 'delete') {
        $product_id = $_REQUEST['product_id'] ?? 0;
        $garment_id = $_REQUEST['garment_id'] ?? 0;

        if ($garment_id) {
            fn_is2or_claid_ai_delete_garment($garment_id);
            fn_set_notification('N', __('notice'), __('is2or_claid_ai.text_garment_deleted'));
        }

        return [CONTROLLER_STATUS_OK, 'products.update?product_id=' . $product_id . '&selected_section=is2or_claid_tryon'];
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'add' || $mode == 'update') {
    $product_id = $_REQUEST['product_id'] ?? 0;
    $product_data = fn_get_product_data($product_id);

    if (empty($product_data)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if ($mode == 'update' && !empty($_REQUEST['garment_id'])) {
        $garment_id = $_REQUEST['garment_id'];

        $garment = fn_is2or_claid_ai_get_garment($garment_id);
        Tygh::$app['view']->assign('garment', $garment);
    }
}