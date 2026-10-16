<?php

use Tygh\Http;
use Tygh\Tygh;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode == 'details') {
    $shipments = Tygh::$app['view']->getTemplateVars('shipments');
    if (!empty($shipments)) {
        foreach ($shipments as $shipment_key => $shipment) {
            if ($shipment['carrier'] == 'tryoto') {
                $tryoto_shipment = db_get_row('SELECT * FROM ?:tryoto_shipments WHERE shipment_id = ?i', $shipment['shipment_id']);

                if (!empty($tryoto_shipment)) {
                    $shipments[$shipment_key]['tracking_number'] = $tryoto_shipment['tryoto_shipping_id'];
                    $shipments[$shipment_key]['carrier_info']['tracking_url'] = $tryoto_shipment['delivery_tracking_url'];
                }
            }
        }
    }

    Tygh::$app['view']->assign('shipments', $shipments);
}

if ($mode == 'tryoto_print_awb') {
    $order_ids = !empty($_REQUEST['order_ids']) ? (array) $_REQUEST['order_ids'] : [];
    if (empty($order_ids)) return [CONTROLLER_STATUS_REDIRECT, 'orders.manage'];

    $api_url = TRYOTO_MODE === 'live' ? TRYOTO_API_URL : TRYOTO_API_TEST_URL;
    $access_token = _get_token($api_url);
    $headers = ['Authorization: Bearer ' . $access_token, 'Accept: application/json'];
    
    // Use an array to store the direct URLs from Tryoto API
    $awb_urls = [];
    
    foreach ($order_ids as $order_id) {
        // Request the AWB print URL from the API
        $response = Http::get($api_url . '/rest/v2/print/' . $order_id, [], ['headers' => $headers]);
        $json_res = json_decode($response, true);
        
        // Collect the direct URL for browser-side rendering
        if (!empty($json_res['printAWBURL'])) {
            $awb_urls[] = $json_res['printAWBURL'];
        }
    }

    if (empty($awb_urls)) {
        fn_set_notification('E', __('error'), "AWB label not found. Please ensure the shipment has been confirmed.");
        return [CONTROLLER_STATUS_REDIRECT, 'orders.manage'];
    }

    // Assign the URLs to the view for the Smarty template
    Tygh::$app['view']->assign('awb_urls', $awb_urls);

    // Render the custom print template instead of binary PDF output
    Tygh::$app['view']->display('addons/tryoto_shipping/views/orders/components/print_awbs.tpl');
    
    // Terminate execution to prevent CS-Cart standard redirection or extra headers
    exit;
}