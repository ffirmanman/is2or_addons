<?php

use Tygh\Http;
use Tygh\Tygh;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode == 'details') {
    $shipments = Tygh::$app['view']->getTemplateVars('shipments');
    if (!empty($shipments)) {
        foreach ($shipments as $shipment_key => $shipment) {
            if ($shipment['carrier'] == 'torod') {
                $torod_shipment = db_get_row('SELECT * FROM ?:torod_shipments WHERE shipment_id = ?i', $shipment['shipment_id']);

                if (!empty($torod_shipment)) {
                    $shipments[$shipment_key]['tracking_number'] = $torod_shipment['torod_tracking_id'];
                    $shipments[$shipment_key]['carrier_info']['tracking_url'] = $torod_shipment['delivery_tracking_url'];
                }
            }
        }
    }

    Tygh::$app['view']->assign('shipments', $shipments);
}

if ($mode == 'torod_print_awb') {
    $order_ids = !empty($_REQUEST['order_ids']) ? (array) $_REQUEST['order_ids'] : [];
    if (empty($order_ids)) return [CONTROLLER_STATUS_REDIRECT, 'orders.manage'];

    $awb_urls = [];

    foreach ($order_ids as $order_id) {
        $awb_url = db_get_field('SELECT awb_url FROM ?:torod_shipments WHERE order_id = ?i', $order_id);
        if (!empty($awb_url)) {
            $awb_urls[] = $awb_url;
        }
    }

    if (empty($awb_urls)) {
        fn_set_notification('E', __('error'), __('torod_awb_not_found'));
        return [CONTROLLER_STATUS_REDIRECT, 'orders.manage'];
    }

    Tygh::$app['view']->assign('awb_urls', $awb_urls);
    Tygh::$app['view']->display('addons/torod_shipping/views/orders/components/print_awbs.tpl');
    exit;
}
