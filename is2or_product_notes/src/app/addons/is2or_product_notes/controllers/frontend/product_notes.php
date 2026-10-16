<?php

use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        if (!defined('AJAX_REQUEST')) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $item_id = $_REQUEST['item_id'];
        $notes = $_REQUEST['notes'];

        $cart = &Tygh::$app['session']['cart'];
        
        $item_id = fn_product_notes_get_cart_item_id_by_product_id($item_id, $cart);
        if ($item_id !== false && !empty($cart['products'][$item_id])) {
            $cart['products'][$item_id]['notes'] = $notes;

            fn_save_cart_content($cart, $auth['user_id']);
        }
        
        Tygh::$app['ajax']->assign('status', 1);
        exit;
    }

    return [CONTROLLER_STATUS_OK];
}
