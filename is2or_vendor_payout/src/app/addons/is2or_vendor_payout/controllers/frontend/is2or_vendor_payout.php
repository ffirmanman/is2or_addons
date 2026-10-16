<?php

defined('BOOTSTRAP') or die('Access denied!');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'complete_order') {
        if ($order_id = $_REQUEST['order_id']) {
            fn_is2or_vendor_payout_complete_order($order_id);

            fn_set_notification('N', __('notice'), __('is2or_vendor_payout.order_completed'));

            return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
        }
    }
    return [CONTROLLER_STATUS_OK];
}