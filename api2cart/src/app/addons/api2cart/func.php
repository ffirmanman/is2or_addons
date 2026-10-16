<?php

use Tygh\Http;
use Tygh\Registry;

if ( ! defined('BOOTSTRAP')) {
    die('Access denied');
}

require_once(Registry::get('config.dir.addons').'api2cart/config.php');

function fn_api2cart_get_reference_product_id($product_id)
{
    return db_get_field(
        "SELECT reference_product_id FROM ?:product_integration WHERE product_id = ?i AND source='api2cart'",
        $product_id
    );
}

function fn_api2cart_delete($endpoint)
{
    if (empty(API2CART_URL)) {
        return null;
    }
    $url = API2CART_URL.$endpoint;

    $response = Http::delete($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key' => API2CART_KEY,
            'x-store-key' => API2CART_STORE_KEY,
        ],
    ]);
    return json_decode($response, true);
}
function fn_api2cart_put($endpoint, $params)
{
    if (empty(API2CART_URL)) {
        return null;
    }
    $url = API2CART_URL.$endpoint;

    $response = Http::put($url, $params, [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key' => API2CART_KEY,
            'x-store-key' => API2CART_STORE_KEY,
        ],
    ]);
    return json_decode($response, true);
}
function fn_api2cart_post($endpoint, $params)
{
    if (empty(API2CART_URL)) {
        return null;
    }
    $url = API2CART_URL.$endpoint;

    $response = Http::post($url, $params, [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key' => API2CART_KEY,
            'x-store-key' => API2CART_STORE_KEY,
        ],
    ]);
    return json_decode($response, true);
}


function fn_api2cart_write_log($message, $filename = 'api2cart.log')
{
    $log_dir = Registry::get('config.dir.logs'); // biasanya: var/logs/
    $file_path = $log_dir.$filename;

    if (is_array($message)) {
        $message = json_encode($message);
    }
    // Ubah array/object jadi string
    if (is_object($message)) {
        $message = print_r($message, true); // atau gunakan json_encode($message, JSON_PRETTY_PRINT)
    }

    $formatted = '['.date('Y-m-d H:i:s').'] '.$message.PHP_EOL;

    file_put_contents($file_path, $formatted, FILE_APPEND);
}

function fn_api2cart_delete_category_post($category_id, $category_deleted)
{
    if (empty($category_id)) {
        return false;
    }

    $referenceAPi2cart = db_get_field(
        "SELECT reference_category_id FROM ?:category_integration WHERE category_id = ?i AND source='api2cart'",
        $category_id
    );

    if ( ! empty($referenceAPi2cart)) {
        fn_api2cart_delete('/category.delete.json?id='.$referenceAPi2cart);

        db_query(
            'DELETE FROM ?:category_integration WHERE category_id=? AND reference_product_id=? ',
            $category_id,
            $referenceAPi2cart
        );
    }

}

function fn_api2cart_update_category_post($category_data, $category_id, $lang_code)
{
    if (empty($category_id)) {
        return false;
    }

    $referenceAPi2cart = db_get_field(
        "SELECT reference_category_id FROM ?:category_integration WHERE category_id = ?i AND source='api2cart'",
        $category_id
    );

    $params = [
        'name' => $category_data['category'] ?? '',
        'description' => $category_data['description'] ?? '',
        'parent_id' => $category_data['parent_id'] ?? 0,
        'avail' => ($category_data['status'] ?? 'A') === 'A' ? 'true' : 'false',
        'created_time' => date('Y-m-d H:i:s'),
        'modified_time' => date('Y-m-d H:i:s'),
        'sort_order' => $category_data['position'] ?? 0,
        'meta_title' => $category_data['page_title'] ?? '',
        'meta_description' => $category_data['meta_description'] ?? '',
        'meta_keywords' => $category_data['meta_keywords'] ?? '',
        'seo_url' => $category_data['seo_name'] ?? '',
    ];

    if ( ! empty($referenceAPi2cart)) {
        $params['id'] = $referenceAPi2cart;
        fn_api2cart_put('/category.update.json', $params);
    } else {
        $response_data = fn_api2cart_post('/category.add.json', $params);
        if (isset($response_data['result'])) {
            db_query(
                "INSERT INTO ?:category_integration (category_id, source, reference_product_id)
                VALUES (?i, 'api2cart', ?i)",
                $category_id,
                $response_data['result']['category_id']
            );
        }
    }
}


function fn_api2cart_delete_product_post($product_id, $product_deleted)
{
    if (empty($product_id)) {
        return false;
    }

    $referenceAPi2cart = fn_api2cart_get_reference_product_id($product_id);

    if(!empty($referenceAPi2cart)) {
        fn_api2cart_delete('/product.delete.json?id='.$referenceAPi2cart);
        db_query('DELETE FROM ?:product_integration WHERE product_id=? AND reference_product_id=? ',
        $product_id, $referenceAPi2cart);
    }
}

function fn_api2cart_update_product_post($product_data, $product_id, $lang_code, $create)
{
    if (empty($product_id)) {
        return false;
    }

    $referenceAPi2cart = fn_api2cart_get_reference_product_id($product_id);
    $auth = Tygh::$app['session']['auth'];
    $_product_data = fn_get_product_data($product_id, $auth, CART_LANGUAGE);


    $image_url = '';
    if ( ! empty($_product_data['main_pair']['detailed']['image_path'])) {
        $image_url = $_product_data['main_pair']['detailed']['image_path'];
    }

    // Atau image_name
    $image_name = '';
    if ( ! empty($_product_data['main_pair']['detailed']['image'])) {
        $image_name = $_product_data['main_pair']['detailed']['image'];
    }

    $brand_name = '';
    if ( ! empty($_product_data['product_features'])) {
        foreach ($_product_data['product_features'] as $feature) {
            if (mb_strtolower($feature['description']) === 'brand' || mb_strtolower(
                    $feature['internal_name']
                ) === 'brand') {
                $brand_name = $feature['variant'] ?? '';
                break;
            }
        }
    }

    $main_category_id = $_product_data['main_category'];

    $referenceCategory = db_get_field(
        "SELECT reference_category_id FROM ?:category_integration WHERE category_id = ?i AND source='api2cart'",
        $main_category_id
    );

    // SEO
    $seo_url = db_get_field(
        "SELECT name FROM ?:seo_names WHERE object_id = ?i AND type = 'p' AND lang_code = ?s",
        $product_id,
        $lang_code
    );

    // Manufacturer (optional, dari fitur)
    $manufacturer = '';
    foreach ($_product_data['product_features'] as $feature) {
        if (mb_strtolower($feature['description']) === 'manufacturer') {
            $manufacturer = $feature['variant'] ?? '';
            break;
        }
    }

    $data = [
        'category_id' => $referenceCategory,
        'name' => $_product_data['product'],
        'description' => $_product_data['full_description'],
        'price' => fn_format_price($_product_data['price']),
        'sku' => $_product_data['product_code'],
        'model' => $_product_data['product_code'],
        'quantity' => $_product_data['amount'],
        'in_stock' => $_product_data['amount'] > 0,
        'width' => floatval($_product_data['width']),
        'height' => floatval($_product_data['height']),
        'length' => floatval($_product_data['length']),
        'dimensions_unit' => 'cm',
        'weight' => floatval($_product_data['weight']),
        'weight_unit' => 'gram',
        'image_url' => $image_url,
        'image_name' => $image_name,
        'brand_name' => $brand_name,
        'manufacturer' => $manufacturer,
        'meta_keywords' => $_product_data['meta_keywords'],
        'meta_description' => $_product_data['meta_description'],
        'seo_url' => $seo_url,
        'created_at' => $_product_data['timestamp'] ? date('Y-m-d H:i:s', $_product_data['timestamp']) : date(
            'Y-m-d H:i:s'
        ),
    ];
    if (!empty($referenceAPi2cart)) {
        $data['id'] = $referenceAPi2cart;
        fn_api2cart_put('/product.update.json', json_encode($data));
    }else {
        $response_data = fn_api2cart_post('/product.add.json', json_encode($data));
        if (isset($response_data['result'])) {
            db_query("INSERT INTO ?:product_integration (product_id, source, reference_product_id)
                VALUES (?i, 'api2cart', ?i)", $product_id, $response_data['result']['product_id']);
        }
    }
    fn_api2cart_sync_product_options($product_id);
}

function fn_api2cart_sync_product_options($product_id)
{
    $options = fn_get_product_options($product_id, CART_LANGUAGE);

    foreach ($options as $option) {
        $option_values = [];
        $value_names = [];

        foreach ($option['variants'] as $i => $variant) {
            $value = strtolower(str_replace(' ', '_', $variant['variant_name']));
            $display = $variant['variant_name'];

            $option_values[] = [
                'value' => $value,
                'display_value' => $display,
                'is_default' => $i === 0 // anggap pertama adalah default
            ];

            $value_names[] = $value;
        }

        $payload = [
            'name' => $option['option_name'],
            'type' => 'option_type_select', // mapping dari CS-Cart 'S' ke API2Cart
            'product_id' => fn_api2cart_get_reference_product_id($product_id),
            'default_option_value' => $value_names[0] ?? '',
            'option_values' => implode(',', $value_names),
            'description' => $option['option_name'],
            'avail' => true,
            'sort_order' => $option['position'] ?? 0,
            'required' => $option['required'] === 'Y',
            'values' => $option_values,
            'clear_cache' => true,
        ];

        fn_api2cart_post('/product.option.add.json', $payload);
    }
}


function fn_process_api2cart_order(array $order_data)
{
    // Contoh mapping awal
    $user_data = [
        'firstname' => $order_data['customer']['first_name'],
        'lastname' => $order_data['customer']['last_name'],
        'email' => $order_data['customer']['email'],
        'phone' => $order_data['customer']['phone'] ?? '',
        'country' => $order_data['shipping_address']['country']['name'] ?? '',

        's_firstname' => $order_data['shipping_address']['first_name'] ?? '',
        's_lastname' => $order_data['shipping_address']['last_name'] ?? '',
        's_address' => $order_data['shipping_address']['address1'] ?? '',
        's_address_2' => $order_data['shipping_address']['address2'] ?? '',
        's_city' => $order_data['shipping_address']['city'] ?? '',
        's_state' => $order_data['shipping_address']['state']['name'] ?? '',
        's_zipcode' => $order_data['shipping_address']['postcode'] ?? '',
        's_phone' => $order_data['shipping_address']['phone'] ?? $order_data['shipping_address']['phone_mobile'] ?? '',
        's_country' => $order_data['shipping_address']['country']['name'] ?? '',
        's_company' => $order_data['shipping_address']['company'] ?? '',
        's_fax' => $order_data['shipping_address']['fax'] ?? '',

        'b_firstname' => $order_data['billing_address']['first_name'] ?? '',
        'b_lastname' => $order_data['billing_address']['last_name'] ?? '',
        'b_address' => $order_data['billing_address']['address1'] ?? '',
        'b_address_2' => $order_data['billing_address']['address2'] ?? '',
        'b_city' => $order_data['billing_address']['city'] ?? '',
        'b_state' => $order_data['billing_address']['state']['name'] ?? '',
        'b_zipcode' => $order_data['billing_address']['postcode'] ?? '',
        'b_phone' => $order_data['billing_address']['phone'] ?? $order_data['billing_address']['phone_mobile'] ?? '',
        'b_country' => $order_data['billing_address']['country']['name'] ?? '',
        'b_company' => $order_data['billing_address']['company'] ?? '',
        'b_fax' => $order_data['billing_address']['fax'] ?? '',
    ];

    $cart = [
        'user_id' => 0,
        'company_id' => Registry::get('runtime.company_id'),
        'timestamp' =>$order_data['create_at']['value'] ?? date('Y-m-d H:i:s'),
        'email' => $user_data['email'],
        'user_data' => $user_data,
        'payment_id' => 0,
        'shipping_ids' => [],
        'products' => [],
        'notes' => '',
        'payment_info' => array(),
        'calculate_shipping' => false,
        'shipping_required' => false,
        'shipping_failed' => false,
        'company_shipping_failed' => false,
        'shipping_cost' => $order_data['totals']['shipping'] ?? 0,
        'display_shipping_cost' => $order_data['totals']['shipping'] ?? 0,
        'total' => $order_data['totals']['total'] ?? 0,
        'subtotal' => $order_data['totals']['subtotal'] ?? 0,
    ];

    foreach ($order_data['order_products'] as $item) {
        $product_id = fn_api2cart_get_product_id_by_reference($item['product_id']);
        if ($product_id) {
            $cart['products'][] = [
                'product_id' => $product_id,
                'amount' => $item['quantity'],
                'base_price' => $item['price'],
                'price' => $item['price_inc_tax'],
            ];
        }
    }
    // todo how to create order to multiple companies? when products different company and handle for shipping
    // Buat order
    $customer_auth = fn_fill_auth([], [], false, 'C');
    [$order_id,] = fn_place_order($cart, $customer_auth, 'save');
    // Simpan mapping ke table order_integration
    db_query("INSERT INTO ?:order_integration ?e", [
        'order_id' => $order_id,
        'external_order_id' => $order_data['id'],
        'source' => 'api2cart',
    ]);
    fn_change_order_status($order_id, 'P', '', fn_get_notification_rules([], true));

    fn_log_event('orders', 'new', ['message' => "Order #$order_id created from API2Cart"]);
}

function fn_api2cart_get_product_id_by_reference($reference_id)
{
    return db_get_field("SELECT product_id FROM ?:product_integration WHERE reference_product_id = ?s AND source='api2cart'",
        $reference_id);
}

