<?php

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied!');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'cancel_order') {
        if ($order_id = $_REQUEST['order_id']) {
            $order_info = fn_is2or_management_order_status_get_order_short_info($order_id);
            $paidStatus = Registry::get('addons.is2or_management_order_status.paid_status');
            $cancelStatus = Registry::get('addons.is2or_management_order_status.cancelled_status');
            if ($order_info && $order_info['status'] === $paidStatus &&
                Tygh::$app['session']['auth']['user_id'] === $order_info['user_id']) {
                fn_change_order_status($order_id, $cancelStatus);
                fn_set_notification('N', __('notice'), __('is2or_management_order_status.cancel_order_success'));
                return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
            }
        }
    }
    return [CONTROLLER_STATUS_OK];
}
