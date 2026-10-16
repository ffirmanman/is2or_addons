<?php

use Tygh\Http;
use Tygh\Languages\Languages;
use Tygh\Registry;

function fn_torod_shipping_clear_cache_post($type, $extra)
{
    $cache_dir = Registry::get('config.dir.cache_static') . 'torod';

    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

function fn_torod_shipping_install()
{
    $services = array(
        array(
            'status' => 'A',
            'module' => 'torod',
            'code' => 'normal',
            'sp_file' => '',
            'description' => 'Normal',
        ),
        array(
            'status' => 'A',
            'module' => 'torod',
            'code' => 'cold',
            'sp_file' => '',
            'description' => 'Cold',
        ),
        array(
            'status' => 'A',
            'module' => 'torod',
            'code' => 'quick',
            'sp_file' => '',
            'description' => 'Quick',
        ),
    );

    foreach ($services as $service) {
        $service['service_id'] = db_get_field(
            'SELECT service_id FROM ?:shipping_services WHERE module = ?s AND code = ?s',
            $service['module'],
            $service['code']
        );

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

function fn_torod_shipping_uninstall()
{
    $service_ids = db_get_fields('SELECT service_id FROM ?:shipping_services WHERE module = ?s', 'torod');
    if (!empty($service_ids)) {
        db_query('DELETE FROM ?:shipping_services WHERE service_id IN (?a)', $service_ids);
        db_query('DELETE FROM ?:shipping_service_descriptions WHERE service_id IN (?a)', $service_ids);
    }
}

function fn_torod_get_access_token()
{
    $api_url = TOROD_MODE === 'production' ? TOROD_PRODUCTION_API_URL : TOROD_API_URL;

    $cache_file_path = Registry::get('config.dir.cache_static') . '/torod/torod_token.json';

    $is_valid_token = false;
    $access_token = '';

    if (file_exists($cache_file_path)) {
        $token_string = fn_get_contents($cache_file_path);
        $token_data = json_decode($token_string, true);
        if (is_array($token_data) && isset($token_data['bearer_token']) && isset($token_data['expires_in'])) {
            if ($token_data['expires_in'] > strtotime('now')) {
                $is_valid_token = true;
                $access_token = $token_data['bearer_token'];
            }
        }
    }

    if (!$is_valid_token) {
        $post_fields = http_build_query(array(
            'client_id' => TOROD_CLIENT_ID,
            'client_secret' => TOROD_CLIENT_SECRET,
        ));

        $response = Http::post($api_url . '/token', $post_fields, array(
            'headers' => array(
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ),
            'timeout' => TOROD_API_TIMEOUT,
        ));

        $response_data = json_decode($response, true);

        if (!empty($response_data['data']['bearer_token'])) {
            $access_token = $response_data['data']['bearer_token'];

            $cache_data = array(
                'bearer_token' => $access_token,
                'expires_in' => strtotime('now') + 86400,
            );
            fn_put_contents($cache_file_path, json_encode($cache_data));
        }
    }

    return $access_token;
}

function fn_torod_shipping_delete_shipments($shipment_ids, $result)
{
    if (!empty($shipment_ids)) {
        db_query('DELETE FROM ?:torod_shipments WHERE shipment_id IN (?n)', $shipment_ids);
    }
}

function fn_torod_resolve_city_id_from_location(array $location)
{
    if (!empty($location['city_id'])) {
        return (int) $location['city_id'];
    }

    if (empty($location['city']) || empty($location['state']) || empty($location['country']) || !function_exists('fn_cities_get_city_ids')) {
        return 0;
    }

    $city_ids = fn_cities_get_city_ids(trim($location['city']), trim($location['state']), trim($location['country']), CART_LANGUAGE);

    if (empty($city_ids) && defined('DESCR_SL') && DESCR_SL !== CART_LANGUAGE) {
        $city_ids = fn_cities_get_city_ids(trim($location['city']), trim($location['state']), trim($location['country']), DESCR_SL);
    }

    return !empty($city_ids) ? (int) reset($city_ids) : 0;
}

function fn_torod_shipping_calculate_cart_taxes_pre(&$cart, $cart_products, $product_groups, $calculate_taxes, $auth)
{
    $torod_session = Tygh::$app['session']['cart']['torod_providers'] ?? array();
    if (!empty($cart['product_groups'])) {
        foreach ($cart['product_groups'] as &$group) {
            if (!empty($group['chosen_shippings'])) {
                foreach ($group['chosen_shippings'] as &$chosen) {
                    if ($chosen['module'] == 'torod') {
                        $s_id = $chosen['shipping_id'];

                        if (!empty($torod_session[$s_id]['torod_provider_id'])) {
                            $chosen['service_params']['torod_provider_id'] = $torod_session[$s_id]['torod_provider_id'];
                            $chosen['service_params']['torod_provider_name'] = $torod_session[$s_id]['torod_provider_name'];
                        } elseif (!empty($chosen['service_code']) && $chosen['service_code'] !== 'normal') {
                            $chosen['service_params']['torod_provider_id'] = $chosen['service_code'];
                            $chosen['service_params']['torod_provider_name'] = $chosen['shipping'];
                        }
                    }
                }
            }
        }
    }
}

function fn_torod_shipping_pre_place_order(&$cart, $allow, $product_groups)
{
    $torod_providers = isset(Tygh::$app['session']['cart']['torod_providers'])
        ? Tygh::$app['session']['cart']['torod_providers']
        : array();

    if (empty($torod_providers)) {
        return;
    }

    // Update in Product Groups
    if (!empty($cart['product_groups'])) {
        foreach ($cart['product_groups'] as $pg_key => $product_group) {
            if (!empty($product_group['chosen_shippings'])) {
                foreach ($product_group['chosen_shippings'] as $ch_key => $chosen_shipping) {
                    $s_id = $chosen_shipping['shipping_id'];
                    if ($chosen_shipping['module'] == 'torod' && isset($torod_providers[$s_id])) {
                        $cart['product_groups'][$pg_key]['chosen_shippings'][$ch_key]['service_params']['torod_provider_id'] = $torod_providers[$s_id]['torod_provider_id'];
                        $cart['product_groups'][$pg_key]['chosen_shippings'][$ch_key]['service_params']['torod_provider_name'] = $torod_providers[$s_id]['torod_provider_name'];
                    }
                }
            }
        }
    }

    if (!empty($cart['shipping'])) {
        foreach ($cart['shipping'] as $s_id => $shipping_data) {
            if ($shipping_data['module'] == 'torod' && isset($torod_providers[$s_id])) {
                $cart['shipping'][$s_id]['service_params']['torod_provider_id'] = $torod_providers[$s_id]['torod_provider_id'];
                $cart['shipping'][$s_id]['service_params']['torod_provider_name'] = $torod_providers[$s_id]['torod_provider_name'];
            }
        }
    }
}

function fn_torod_shipping_delete_shipments_pre(&$shipment_ids)
{
    if (!empty($shipment_ids) && is_array($shipment_ids)) {
        foreach ($shipment_ids as $shipment_id) {
            $shipment_data = db_get_row(
                "SELECT carrier FROM ?:shipments WHERE shipment_id = ?i",
                $shipment_id
            );
            if ($shipment_data && $shipment_data['carrier'] === 'torod') {
                _delete_torod_shipment($shipment_id);
            }
        }
    } else {
        $shipment_data = db_get_row(
            "SELECT carrier FROM ?:shipments WHERE shipment_id = ?i",
            $shipment_ids
        );
        if ($shipment_data && $shipment_data['carrier'] === 'torod') {
            _delete_torod_shipment($shipment_ids);
        }
    }
}

function _delete_torod_shipment($shipment_id)
{
    $torod_order_id = db_get_field('SELECT torod_order_id FROM ?:torod_shipments WHERE shipment_id = ?i', $shipment_id);

    if ($torod_order_id) {
        fn_torod_api_post('/order/cancel', array('order_id' => $torod_order_id), 'form');
    }
}

function fn_torod_shipping_create_shipment_post($shipment_data, $order_info, $group_key, $all_products, $shipment_id)
{
    if ($shipment_data['carrier'] != 'torod') {
        return;
    }

    // Validate warehouse code exists for the vendor
    $company = fn_get_company_data($order_info['company_id']);
    if (empty($company['warehouse_code'])) {
        fn_set_notification('E', __('error'), __('torod_missing_warehouse_code'));
        return;
    }

    // Idempotency check
    $existing_order = db_get_field(
        'SELECT torod_order_id FROM ?:torod_shipments WHERE order_id = ?i AND company_id = ?i',
        $order_info['order_id'],
        $order_info['company_id']
    );
    if (!empty($existing_order)) {
        fn_set_notification('W', __('warning'), __('torod_shipment_already_exists'));
        return;
    }

    // Insert initial placeholder row
    $insert_data = array(
        'company_id' => $order_info['company_id'],
        'order_id' => $order_info['order_id'],
        'torod_order_id' => '',
        'shipment_id' => $shipment_id,
        'status' => 'pending',
        'created_at' => TIME,
        'updated_at' => TIME,
    );
    db_query('INSERT INTO ?:torod_shipments ?e', $insert_data);

    // Get customer address info
    $user_data = array_values($order_info['product_groups'][0]['products'])[0]['user_data'];
    if ($user_data['ship_to_another'] == 0) {
        $latitude = $user_data['b_latitude'] ?? '';
        $longitude = $user_data['b_longitude'] ?? '';
        $phone = !empty($order_info['phone']) ? $order_info['phone'] : $order_info['b_phone'];
    } else {
        $latitude = $user_data['s_latitude'] ?? '';
        $longitude = $user_data['s_longitude'] ?? '';
        $phone = !empty($order_info['phone']) ? $order_info['phone'] : $order_info['s_phone'];
    }

    $package_info_full = $order_info['product_groups'][0]['package_info_full'];
    $package_info = isset($order_info['product_groups'][0]['package_info_full']['packages'])
        ? $order_info['product_groups'][0]['package_info_full']['packages']
        : array();

    // Calculate weight
    $weight_data = fn_expand_weight($order_info['product_groups'][0]['package_info_full']['W']);
    $shipment_weight = $weight_data['plain'] * Registry::get('settings.General.weight_symbol_grams');
    $shipment_weight = $shipment_weight / 1000;
    if (round($shipment_weight, 1) == 0) {
        $shipment_weight = 1.0;
    }
    $shipment_weight = round($shipment_weight, 1);

    // Build item description
    $item_names = array();
    foreach ($shipment_data['products'] as $key => $puid) {
        if ($puid > 0) {
            $item_names[] = $order_info['product_groups'][0]['products'][$key]['product'];
        }
    }
    $item_description = implode(', ', array_slice($item_names, 0, 5));

    // Payment type
    $cod_payment_method_id = TOROD_COD_PAYMENT_METHOD_ID;
    $payment = ($cod_payment_method_id == $order_info['payment_method']['payment_id']) ? 'COD' : 'Prepaid';

    $resolved_city_id = fn_torod_resolve_city_id_from_location($package_info_full['location']);

    // Build /create payload
    $data = array(
        'name'            => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'email'           => $order_info['email'],
        'phone_number'    => preg_replace('/\D+/', '', $phone),
        'item_description'=> $item_description,
        'order_total'     => $order_info['total'],
        'payment'         => $payment,
        'weight'          => $shipment_weight,
        'no_of_box'       => 1,
        'type'            => 'address_city',
        'address'         => $package_info_full['location']['address'] ?? '',
        'city_id'         => $resolved_city_id,
        'locate_address'  => $package_info_full['location']['address'] ?? '',
        'latitude'        => $latitude,
        'longitude'       => $longitude,
        'reference_id'    => $order_info['order_id'],
        'warehouse'       => $company['warehouse_code'],
    );

    // Fire SPL enrichment hooks
    fn_set_hook('torod_create_order_data', $data, $order_info);
    fn_set_hook('torod_create_shipment_sender_data', $data, $order_info);

    if (empty($data['city_id'])) {
        fn_set_notification('E', __('error'), 'Torod: missing city_id for address_city type');
        db_query('DELETE FROM ?:torod_shipments WHERE shipment_id = ?i', $shipment_id);
        return;
    }

    $access_token = fn_torod_get_access_token();

    if (empty($access_token)) {
        fn_set_notification('E', __('error'), 'Torod: Failed to obtain access token');
        db_query('DELETE FROM ?:torod_shipments WHERE shipment_id = ?i', $shipment_id);
        return;
    }

    // Step 1: Create order
    $response = fn_torod_api_post('/order/create', $data, 'form');
    $result = $response['data'] ?? array();

    if (empty($result['data']['order_id'])) {
        $error_msg = $result['message'] ?? 'Torod order creation failed';
        fn_set_notification('E', __('error'), 'Torod API Error: ' . $error_msg);
        db_query('DELETE FROM ?:torod_shipments WHERE shipment_id = ?i', $shipment_id);
        return;
    }

    $torod_order_id = $result['data']['order_id'];

    // Step 2: Ship/process
    $ship_data = array(
        'order_id' => $torod_order_id,
        'warehouse' => $company['warehouse_code'],
        'type' => $order_info['shipping'][0]['service_params']['service_type'] ?? 'normal',
        'courier_partner_id' => $order_info['shipping'][0]['service_params']['torod_provider_id'] ?? '',
    );

    $ship_response = fn_torod_api_post('/order/ship/process', $ship_data, 'form');
    $ship_result = $ship_response['data'] ?? array();

    $torod_tracking_id = $ship_result['data']['tracking_id'] ?? '';
    $awb_url = $ship_result['data']['awb_url'] ?? '';
    $courier_name = $ship_result['data']['courier_name'] ?? '';
    $delivery_tracking_url = $ship_result['data']['tracking_url'] ?? '';

    // Update torod_shipments row
    $update_data = array(
        'torod_order_id' => $torod_order_id,
        'torod_tracking_id' => $torod_tracking_id,
        'courier_name' => $courier_name,
        'delivery_tracking_url' => $delivery_tracking_url,
        'awb_url' => $awb_url,
        'status' => 'created',
        'updated_at' => TIME,
    );
    db_query('UPDATE ?:torod_shipments SET ?u WHERE shipment_id = ?i', $update_data, $shipment_id);

    // Update CS-Cart shipment with tracking number
    if (!empty($torod_tracking_id)) {
        db_query('UPDATE ?:shipments SET tracking_number = ?s WHERE shipment_id = ?i', $torod_tracking_id, $shipment_id);
    }
}

function fn_torod_shipping_check_shipment($shipment_id)
{
    $awb_url = db_get_field('SELECT awb_url FROM ?:torod_shipments WHERE shipment_id = ?i', $shipment_id);
    if ($awb_url) {
        return $awb_url;
    } else {
        return '';
    }
}

function fn_torod_generate_warehouse_code()
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $exists = db_get_field('SELECT warehouse_code FROM ?:companies WHERE warehouse_code = ?s', $code);
    } while ($exists);

    return $code;
}

function fn_torod_create_warehouse_address($company_id, $warehouse_code)
{
    $company = db_get_row('SELECT * FROM ?:companies WHERE company_id = ?i', $company_id);
    if (empty($company)) {
        return array('success' => false, 'error' => 'Company not found');
    }

    $api_url = TOROD_MODE === 'production' ? TOROD_PRODUCTION_API_URL : TOROD_API_URL;
    $access_token = fn_torod_get_access_token();

    if (empty($access_token)) {
        return array('success' => false, 'error' => 'Failed to obtain Torod access token');
    }

    $data = array(
        'warehouse_name' => $company['company'] ?? $company['name'] ?? '',
        'warehouse' => $warehouse_code,
        'contact_name' => $company['contact_name'] ?? $company['company'] ?? '',
        'phone_number' => preg_replace('/\D+/', '', $company['phone'] ?? $company['b_phone'] ?? ''),
        'email' => $company['email'] ?? '',
        'zip_code' => $company['zipcode'] ?? '',
        'short_address' => $company['spl_short_address'] ?? '',
        'type' => 'address_city',
        'address' => $company['spl_street'] ?? $company['address'] ?? '',
        'city_id' => $company['spl_city_id'] ?? '3',
    );

    $response = Http::post($api_url . '/create/address', http_build_query($data), array(
        'headers' => array(
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json',
        ),
        'timeout' => TOROD_API_TIMEOUT,
    ));

    $result = json_decode($response, true);
    if (!empty($result['status'])) {
        db_query('UPDATE ?:companies SET warehouse_code = ?s, warehouse_code_created_at = ?i WHERE company_id = ?i',
            $warehouse_code, TIME, $company_id);
        return array('success' => true, 'warehouse_code' => $warehouse_code);
    }

    return array('success' => false, 'error' => $result['message'] ?? 'Unknown Torod API error');
}

function fn_torod_shipping_update_company_post($company_data, $company_id, $lang_code, $action)
{
    if (!isset($company_data['status']) || $company_data['status'] !== 'A') {
        return;
    }

    $existing_code = db_get_field('SELECT warehouse_code FROM ?:companies WHERE company_id = ?i', $company_id);
    if (!empty($existing_code)) {
        return;
    }

    $warehouse_code = fn_torod_generate_warehouse_code();
    $result = fn_torod_create_warehouse_address($company_id, $warehouse_code);

    if ($result['success']) {
        db_query('UPDATE ?:companies SET warehouse_code = ?s, warehouse_code_created_at = ?i WHERE company_id = ?i',
            $warehouse_code, TIME, $company_id);
    } else {
        fn_log_event('general', 'runtime', array(
            'message' => 'Torod warehouse creation failed for company ' . $company_id . ': ' . ($result['error'] ?? 'unknown')
        ));
    }
}

function fn_torod_api_get($endpoint, $params = array())
{
    $api_url = TOROD_MODE === 'production' ? TOROD_PRODUCTION_API_URL : TOROD_API_URL;
    $access_token = fn_torod_get_access_token();
    if (empty($access_token)) {
        return array('success' => false, 'error' => 'Failed to obtain access token');
    }
    $url = $api_url . $endpoint;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    $response = Http::get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/json',
        ),
        'timeout' => TOROD_API_TIMEOUT,
    ));
    return array('success' => true, 'data' => json_decode($response, true));
}

function fn_torod_api_post($endpoint, $data = array(), $mode = 'form')
{
    $api_url = TOROD_MODE === 'production' ? TOROD_PRODUCTION_API_URL : TOROD_API_URL;
    $access_token = fn_torod_get_access_token();
    if (empty($access_token)) {
        return array('success' => false, 'error' => 'Failed to obtain access token');
    }

    $headers = array(
        'Authorization: Bearer ' . $access_token,
        'Accept: application/json',
    );

    if ($mode === 'json') {
        $body = json_encode($data);
        $headers[] = 'Content-Type: application/json';
    } else {
        $body = http_build_query($data);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    $response = Http::post($api_url . $endpoint, $body, array(
        'headers' => $headers,
        'timeout' => TOROD_API_TIMEOUT,
    ));
    return array('success' => true, 'data' => json_decode($response, true));
}

function fn_torod_get_all_countries() { return fn_torod_api_get('/get-all/countries'); }
function fn_torod_get_all_regions($country_id = 1) { return fn_torod_api_get('/get-all/regions', array('country_id' => $country_id)); }
function fn_torod_get_all_cities($region_id) { return fn_torod_api_get('/get-all/cities', array('region_id' => $region_id)); }
function fn_torod_get_all_districts($city_id) { return fn_torod_api_get('/get-all/districts', array('cities_id' => $city_id)); }
function fn_torod_get_all_courier_partners() { return fn_torod_api_get('/get-all/courier/partners'); }
function fn_torod_get_all_shipper_types() { return fn_torod_api_get('/get-all/shipper-types'); }
function fn_torod_address_list() { return fn_torod_api_get('/address/list'); }
function fn_torod_address_details($warehouse_code) { return fn_torod_api_post('/address/details', array('warehouse' => $warehouse_code), 'form'); }
function fn_torod_update_address($address_id, $data) { return fn_torod_api_post('/update/address/' . $address_id, $data, 'form'); }
function fn_torod_order_list() { return fn_torod_api_get('/order/list'); }
function fn_torod_order_details($order_id) { return fn_torod_api_post('/order/details', array('order_id' => $order_id), 'form'); }
function fn_torod_shipment_details($tracking_id) { return fn_torod_api_post('/shipment/details', array('tracking_id' => $tracking_id), 'form'); }
function fn_torod_track_order($tracking_id) { return fn_torod_api_post('/order/track', array('tracking_id' => $tracking_id), 'form'); }
function fn_torod_cancel_shipment($tracking_or_order_id) { return fn_torod_api_post('/shipments/cancel', array('tracking_or_order_id' => $tracking_or_order_id), 'form'); }
function fn_torod_get_wallet_balance() { return fn_torod_api_get('/get-wallet-balance'); }

function fn_torod_get_order_statuses()
{
    $variants = array(
        '' => '-- ' . __('none') . ' --'
    );

    $statuses = db_get_hash_single_array(
        "SELECT s.status, sd.description
         FROM ?:statuses AS s
         LEFT JOIN ?:status_descriptions AS sd
            ON sd.status_id = s.status_id
         WHERE s.type IN (?a)
            AND sd.lang_code = ?s
         ORDER BY s.type, sd.description ASC",
        array('status', 'description'),
        array(STATUSES_ORDER),
        CART_LANGUAGE
    );

    return $variants + $statuses;
}

function fn_settings_variants_addons_torod_shipping_webhook_ready_for_pickup()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_in_transit()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_delivered()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_failed_to_deliver()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_failed_to_attempt()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_cancelled()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_rto()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_damage()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_lost()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_pending_pickup()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_picked_up()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_out_for_delivery()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_partial_delivery()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_rescheduled()
{
    return fn_torod_get_order_statuses();
}

function fn_settings_variants_addons_torod_shipping_webhook_rto_in_progress()
{
    return fn_torod_get_order_statuses();
}
