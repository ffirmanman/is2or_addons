<?php

use Tygh\Http;
use Tygh\Languages\Languages;
use Tygh\Registry;

function fn_tryoto_shipping_clear_cache_post($type, $extra)
{
    $cache_dir = Registry::get('config.dir.cache_static') . 'tryoto';

    if (is_dir($cache_dir)) {
        // Ambil semua file di dalam direktori tersebut
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file); // Hapus filenya satu per satu
            }
        }
    }
}

function fn_tryoto_shipping_install()
{
    $services = array(
        array(
            'status' => 'A',
            'module' => 'tryoto',
            'code' => 'express',
            'sp_file' => '',
            'description' => 'Express',
        ),
        array(
            'status' => 'A',
            'module' => 'tryoto',
            'code' => 'sameDay',
            'sp_file' => '',
            'description' => 'Same Day',
        ),
        array(
            'status' => 'A',
            'module' => 'tryoto',
            'code' => 'fastDelivery',
            'sp_file' => '',
            'description' => 'Fast Delivery',
        ),
        array(
            'status' => 'A',
            'module' => 'tryoto',
            'code' => 'coldDelivery',
            'sp_file' => '',
            'description' => 'Cold Delivery',
        ),
        array(
            'status' => 'A',
            'module' => 'tryoto',
            'code' => 'heavyAndBulky',
            'sp_file' => '',
            'description' => 'Heavy and Bulky',
        ),
        array(
            'status' => 'A',
            'module' => 'tryoto',
            'code' => 'electronicAndHeavy',
            'sp_file' => '',
            'description' => 'Electronic and Heavy',
        )

    );

    foreach ($services as $service) {
        $service['service_id'] = db_get_field('SELECT service_id FROM ?:shipping_services WHERE module = ?s AND code = ?s', $service['module'], $service['code']);

        if (empty($service['service_id'])) {
            $service['service_id'] = db_query('INSERT INTO ?:shipping_services ?e', $service);
        }

        $languages = Languages::getAll();
        foreach ($languages as $lang_code => $lang_data) {

            $service['lang_code'] = $lang_code;

            db_query('INSERT INTO ?:shipping_service_descriptions ?e', $service);
        }
    }


}

function fn_tryoto_shipping_uninstall()
{
    $service_ids = db_get_fields('SELECT service_id FROM ?:shipping_services WHERE module = ?s', 'tryoto');
    if (!empty($service_ids)) {
        db_query('DELETE FROM ?:shipping_services WHERE service_id IN (?a)', $service_ids);
        db_query('DELETE FROM ?:shipping_service_descriptions WHERE service_id IN (?a)', $service_ids);
    }
}


function fn_tryoto_shipping_create_shipment_post($shipment_data, $order_info, $group_key, $all_products, $shipment_id)
{
    // Only process if it's Tryoto shipping
    if ($shipment_data['carrier'] == 'tryoto') {
        $shipment_data['tryoto_data'] = array(
            'company_id' => $order_info['company_id'],
            'order_id' => $order_info['order_id'],
            'tryoto_id' => '' ,
            'shipment_id' => $shipment_id
        );


        $insert_id = db_query('INSERT INTO ?:tryoto_shipments ?e', $shipment_data['tryoto_data']);
        $shipment_data['tryoto_data']['id'] = $insert_id;
        db_query('UPDATE ?:shipments set status = ?s WHERE shipment_id = ?i', 'A', $shipment_id);

        $cod_payment_method_id = TRYOTO_COD_PAYMENT_METHOD_ID;
        $weight_data = fn_expand_weight($order_info['product_groups'][0]['package_info_full']['W']);
        $shipment_weight = $weight_data['plain'] * Registry::get('settings.General.weight_symbol_grams');
        $shipment_weight = $shipment_weight / 1000;

        if (round($shipment_weight, 1) == 0) {
            $shipment_weight = 1.0;
        }
        $shipment_weight = round($shipment_weight, 1);

        $package_info = isset($order_info['product_groups'][0]['package_info_full']['packages']) ? $order_info['product_groups'][0]['package_info_full']['packages'] : array();
        $length = 5.0;
        $width = 5.0;
        $height = 5.0;
        if (!empty($package_info)) {
            foreach ($package_info as $package) {
                $length = !empty($package['shipping_params']['box_length']) ? $package['shipping_params']['box_length'] : 5;
                $width = !empty($package['shipping_params']['box_width']) ? $package['shipping_params']['box_width'] : 5;
                $height = !empty($package['shipping_params']['box_height']) ? $package['shipping_params']['box_height'] : 5;
            }
        }


        $api_url = TRYOTO_MODE === 'live' ? TRYOTO_API_URL : TRYOTO_API_TEST_URL;

        $package_info_full = $order_info['product_groups'][0]['package_info_full'];

        $packageCount = 0;

        $items = array();
        foreach ($shipment_data['products'] as $key => $puid) {
            if ($puid > 0) {
                $items[$packageCount]['productId'] = $order_info['product_groups'][0]['products'][$key]['product_id'];
                $items[$packageCount]['name'] = $order_info['product_groups'][0]['products'][$key]['product'];
                $items[$packageCount]['price'] = $order_info['product_groups'][0]['products'][$key]['price'];
                $items[$packageCount]['quantity'] = $puid;
                $items[$packageCount]['sku'] = $order_info['product_groups'][0]['products'][$key]['product_code'];
                $items[$packageCount]['image'] = $order_info['product_groups'][0]['products'][$key]['main_pair']['detailed']['image_path'];

                $packageCount++;
            }
        }

        $data = array(
            'orderId' => $order_info['order_id'],
            'parentOrderId' => $order_info['parent_order_id'],
            'createShipment' => false,
            'deliveryOptionId' => $order_info['shipping'][0]['service_params']['tryoto_provider_id'] ?? '',
            'storeName' => fn_get_storefront($order_info['storefront_id'])->name,
            'payment_method' => ($cod_payment_method_id == $order_info['payment_method']['payment_id']) ? 'cod' : 'paid',
            'amount' => $order_info['total'],
            'amount_due' => ($cod_payment_method_id == $order_info['payment_method']['payment_id']) ? $order_info['total'] : 0,
            'shippingAmount' => $order_info['shipping_cost'],
            'subtotal' => $order_info['subtotal'],
            'currency' => $order_info['secondary_currency'],
            'shippingNotes' => $shipment_data['comments'],
            'packageCount' => $packageCount,
            'packageWeight' => $shipment_weight,
            'boxWidth' => round($width, 2),
            'boxLength' => round($length, 2),
            'boxHeight' => round($height, 2),
            //'senderName' => fn_get_company_name($order_info['company_id']),
            'senderInformation' => array(
                'senderAddressName' => fn_get_company_name($order_info['company_id']),
                'senderId' => $order_info['company_id'],
                'senderFullName' => $package_info_full['origination']['name'],
                'senderMobile' => $package_info_full['origination']['phone'],
                //'senderEmail' => "test@example.com",
                'senderCountry' => $package_info_full['origination']['country'],
                'senderCity' => $package_info_full['origination']['city'],
                //'senderCity' => 'riyadh',
                'senderPostcode' => $package_info_full['origination']['zipcode'],
                //'senderPostcode' => '11564',
                'senderAddressLine' => $package_info_full['origination']['address'],
                //'senderAddressLine' => 'dddd'

            ),
            'customer' => array(
                'name' => $order_info['firstname'] . ' ' . $order_info['lastname'],
                'email' => $order_info['email'],
                'mobile' => !empty($order_info['phone']) ? $order_info['phone'] : @$order_info['b_phone'],
                'address' => $package_info_full['location']['address'],
                'city' => $package_info_full['location']['city'],
                //'city' => 'riyadh',
                'country' => $package_info_full['location']['country'],
                'postcode' => $package_info_full['location']['zipcode'],
            ),
            'items' => $items
        );

        // Set latitude and longitude for vendor
        $company_data = fn_get_company_data($order_info['company_id']);
        if (isset($company_data['latitude']) && !empty(floatval($company_data['latitude'])) && !empty(floatval
            ($company_data['longitude']))) {
            $data['senderInformation']['lat'] = $company_data['latitude'];
            $data['senderInformation']['lon'] = $company_data['longitude'];
        }

        // set latitude and longitude for customer
        $user_data = array_values($order_info['product_groups'][0]['products'])[0]['user_data'];
        if ($user_data['ship_to_another'] == 0) {
            $latidude = $user_data['b_latitude'];
            $longitude = $user_data['b_longitude'];
        } else {
            $latidude = $user_data['s_latitude'] ?? '';
            $longitude = $user_data['s_longitude'] ?? '';
        }

        if (!empty(floatval($latidude)) && !empty(floatval($longitude))) {
            $data['customer']['lat'] = $latidude;
            $data['customer']['lon'] = $longitude;
        }

        /* return; */
        $access_token = _get_token($api_url);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];

        $api_url_for_delivery = $api_url . '/rest/v2/createOrder';

        $response = Http::post($api_url_for_delivery, json_encode($data), array('headers' => $headers));
        $response = json_decode($response, true);

        if (!empty($response) && !$response['success']) {
            fn_set_notification('E', __('error'), $response['otoErrorMessage']);
            $shipment_data['products'] = array();
        }

        if (!empty($response) && $response['success']) {
            // create shipment
            $access_token = _get_token($api_url);

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token
            ];


            $data = array(
                'orderId' => $order_info['order_id'],
                'deliveryOptionId' => $order_info['shipping'][0]['service_params']['tryoto_provider_id'] ?? ''
            );

            $api_url_for_delivery = $api_url . '/rest/v2/createShipment';

            $response = Http::post($api_url_for_delivery, json_encode($data), array('headers' => $headers));
            $response = json_decode($response, true);


            if (!empty($response) && !$response['success']) {
                fn_set_notification('E', __('error'), $response['otoErrorMessage']);
                $shipment_data['products'] = array();
            }

        }
    }

}

//function fn_tryoto_shipping_get_shipping_info_post($shipping_id, &$shipping_info)
//{
//    if (!empty($shipping_info['service_params']) && !empty($shipping_info['module']) && $shipping_info['module'] == 'tryoto') {
//        $shipping_info['service_params']['test_mode'] = isset($shipping_info['service_params']['test_mode']) ? $shipping_info['service_params']['test_mode'] : 'Y';
//    }
//}

function fn_tryoto_shipping_delete_shipments($shipment_ids, $result)
{
    if (!empty($shipment_ids)) {
        db_query('DELETE FROM ?:tryoto_shipments WHERE shipment_id IN (?n)', $shipment_ids);
    }
}

function fn_tryoto_shipping_calculate_cart_taxes_pre(&$cart, $cart_products, $product_groups, $calculate_taxes, $auth) {
    // Ambil data dari session langsung
    $tryoto_session = Tygh::$app['session']['cart']['tryoto_providers'] ?? [];
    if (!empty($cart['product_groups'])) {
        foreach ($cart['product_groups'] as &$group) {
            if (!empty($group['chosen_shippings'])) {
                foreach ($group['chosen_shippings'] as &$chosen) {

                    if ($chosen['module'] == 'tryoto') {
                        $s_id = $chosen['shipping_id'];

                        // 1. Cek dari Session khusus kita
                        if (!empty($tryoto_session[$s_id]['tryoto_provider_id'])) {
                            $chosen['service_params']['tryoto_provider_id'] = $tryoto_session[$s_id]['tryoto_provider_id'];
                            $chosen['service_params']['tryoto_provider_name'] = $tryoto_session[$s_id]['tryoto_provider_name'];
                        }
                        // 2. Fallback: Jika session kosong, ambil dari service_code (karena kita set di processRates)
                        elseif (!empty($chosen['service_code']) && $chosen['service_code'] !== 'express') {
                            $chosen['service_params']['tryoto_provider_id'] = $chosen['service_code'];
                            $chosen['service_params']['tryoto_provider_name'] = $chosen['shipping'];
                        }
                    }
                }
            }
        }
    }
}


function fn_tryoto_shipping_pre_place_order(&$cart, $allow, $product_groups)
{
    $tryoto_providers = isset(Tygh::$app['session']['cart']['tryoto_providers'])
        ? Tygh::$app['session']['cart']['tryoto_providers']
        : array();

    if (empty($tryoto_providers)) {
        return;
    }

    // 1. Update di Product Groups (Untuk tampilan checkout)
    if (!empty($cart['product_groups'])) {
        foreach ($cart['product_groups'] as $pg_key => $product_group) {
            if (!empty($product_group['chosen_shippings'])) {
                foreach ($product_group['chosen_shippings'] as $ch_key => $chosen_shipping) {
                    $s_id = $chosen_shipping['shipping_id'];
                    if ($chosen_shipping['module'] == 'tryoto' && isset($tryoto_providers[$s_id])) {
                        $cart['product_groups'][$pg_key]['chosen_shippings'][$ch_key]['service_params']['tryoto_provider_id'] = $tryoto_providers[$s_id]['tryoto_provider_id'];
                        $cart['product_groups'][$pg_key]['chosen_shippings'][$ch_key]['service_params']['tryoto_provider_name'] = $tryoto_providers[$s_id]['tryoto_provider_name'];
                    }
                }
            }
        }
    }

    if (!empty($cart['shipping'])) {
        foreach ($cart['shipping'] as $s_id => $shipping_data) {
            if ($shipping_data['module'] == 'tryoto' && isset($tryoto_providers[$s_id])) {
                $cart['shipping'][$s_id]['service_params']['tryoto_provider_id'] = $tryoto_providers[$s_id]['tryoto_provider_id'];
                $cart['shipping'][$s_id]['service_params']['tryoto_provider_name'] = $tryoto_providers[$s_id]['tryoto_provider_name'];
            }
        }
    }
}

function fn_tryoto_shipping_delete_shipments_pre(&$shipment_ids)
{

    if (!empty($shipment_ids) && is_array($shipment_ids)) {
        foreach ($shipment_ids as $shipment_id) {
            // 1. Ambil data shipment (tracking number) dari database
            $shipment_data = db_get_row(
                "SELECT carrier FROM ?:shipments WHERE shipment_id = ?i",
                $shipment_id
            );
            if ($shipment_data && $shipment_data['carrier'] === 'tryoto') {
                _delete_tryoto_shipment($shipment_id);
            }
        }
    } else {
        // 1. Ambil data shipment (tracking number) dari database
        $shipment_data = db_get_row(
            "SELECT carrier FROM ?:shipments WHERE shipment_id = ?i",
            $shipment_ids
        );
        if ($shipment_data && $shipment_data['carrier'] === 'tryoto') {
            _delete_tryoto_shipment($shipment_ids);
        }
    }

}

function _delete_tryoto_shipment($shipment_id) {
    $tryoto_shipping_id = db_get_field('SELECT tryoto_shipping_id FROM ?:tryoto_shipments WHERE shipment_id = ?i', $shipment_id);
    $tryoto_order_id = db_get_field('SELECT order_id FROM ?:tryoto_shipments WHERE shipment_id = ?i', $shipment_id);

    $api_url = TRYOTO_MODE === 'live' ? TRYOTO_API_URL : TRYOTO_API_TEST_URL;
    $access_token = _get_token($api_url);
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ];

    // cancel order
//    $api_url_cancel_order = $api_url . '/rest/v2/cancelOrder';
//    $data = array(
//        'orderId' => $tryoto_order_id,
//    );
//
//    Http::post($api_url_cancel_order, json_encode($data), array('headers' => $headers));

    // cancel shipment
    if($tryoto_shipping_id) {
        $api_url_for_delivery = $api_url . '/rest/v2/cancelShipment';
        $data = array(
            'orderId' => $tryoto_order_id,
            'shipmentId' => $tryoto_shipping_id
        );
        $response = Http::post($api_url_for_delivery, json_encode($data), array('headers' => $headers));
        $response = json_decode($response, true);

        if (!empty($response) && !$response['success']) {
            fn_set_notification('E', __('error'), $response['otoErrorMessage']);
            return false;
        }
        return true;
    } else {
        return true;
    }

}



function _get_token($api_url)
{
    $api_url = $api_url . '/rest/v2/refreshToken';

    $tryoto_refresh_token = TRYOTO_CLIENT_ID;

    // Prepare request data
    $request_data = [
        'refresh_token' => $tryoto_refresh_token
    ];

    // Set headers
    $headers = [
        'Content-Type: application/json',
    ];

    // Check existing token
    $cache_file_path = Registry::get('config.dir.cache_static') . '/tryoto/tryoto_token.json';

    $is_valid_token = false;
    $access_token = '';
    $expires_in = 0;
    $token_type = 0;

    if (file_exists($cache_file_path)) {
        $tryoto_token_string = fn_get_contents($cache_file_path);
        $arr_tryoto_token = json_decode($tryoto_token_string, true);
        if (is_array($arr_tryoto_token) && count($arr_tryoto_token) == 3) {
            if ($arr_tryoto_token['expires_in'] > strtotime("now")) {
                $is_valid_token = true;
                $access_token = $arr_tryoto_token['access_token'];
            }
        }
    }

    if (!$is_valid_token) {

        // Make the API request
        $response = Http::post($api_url, json_encode($request_data), [
            'headers' => $headers,
        ]);

        $response_data = json_decode($response, true);

        $http_status = Http::getStatus();

        if ($http_status >= 400) {
            $access_token = '';
            $expires_in = 0;
            $token_type = '';
        } else {
            $access_token = $response_data['access_token'];
            $expires_in = $response_data['expires_in'];
            $token_type = $response_data['token_type'];
        }

        $tryoto_token_string = json_encode(
            array(
                'access_token' => $access_token,
                'expires_in' => $expires_in + strtotime('now'),
                'token_type' => $token_type
            )
        );
        fn_put_contents($cache_file_path, $tryoto_token_string);
    }
    return $access_token;
}

function fn_tryoto_shipping_check_shipment($shipment_id)
{
    $awb_url = db_get_field('SELECT awb_url FROM ?:tryoto_shipments WHERE shipment_id=?i', $shipment_id);
    if ($awb_url) {
        return $awb_url;
    } else {
        return '';
    }
}

function fn_settings_variants_addons_tryoto_shipping_webhook_delivered() {
    return fn_tryoto_get_order_statuses();
}

function fn_settings_variants_addons_tryoto_shipping_webhook_picked_up() {
    return fn_tryoto_get_order_statuses();
}

function fn_settings_variants_addons_tryoto_shipping_webhook_searching_driver() {
    return fn_tryoto_get_order_statuses();
}

function fn_settings_variants_addons_tryoto_shipping_webhook_shipment_created() {
    return fn_tryoto_get_order_statuses();
}

function fn_settings_variants_addons_tryoto_shipping_webhook_shipment_cancelled() {
    return fn_tryoto_get_order_statuses();
}

function fn_settings_variants_addons_tryoto_shipping_webhook_cancelled() {
    return fn_tryoto_get_order_statuses();
}

function fn_tryoto_get_order_statuses() {
    $variants = [
        '' => '-- ' . __("none") . ' --'
    ];

    $statuses = db_get_hash_single_array(
        "SELECT s.status, sd.description 
         FROM ?:statuses AS s 
         LEFT JOIN ?:status_descriptions AS sd 
            ON sd.status_id = s.status_id 
         WHERE s.type IN (?a) 
            AND sd.lang_code = ?s 
         ORDER BY s.type, sd.description ASC",
        ['status', 'description'],
        [STATUSES_ORDER],
        CART_LANGUAGE
    );

    return $variants + $statuses;
}
