<?php

use IS2OR\ClaidAI\Api;
use Tygh\Enum\IECategoryTypes;
use Tygh\Enum\IEProductTypes;
use Tygh\Enum\ProductZeroPriceActions;
use Tygh\Enum\UserTypes;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Tools\Url;

function fn_is2or_claid_ai_install() {
    fn_define('DISABLE_HOOK_CACHE', true);
    fn_define("IS2OR_CLAID_AI_INSTALL", true);

    $current_hooks = Registry::get('hooks');
    Registry::set('hooks', [], true);

    $category_id = fn_update_category(
        [
            'category'             => __('is2or_claid_ai.image_enhancer'),
            'parent_id'            => 0,
            'description'          => '',
            'status'               => 'H',
            'page_title'           => '',
            'meta_description'     => '',
            'meta_keywords'        => '',
            'usergroup_ids'        => 0,
            'position'             => '',
            'product_details_view' => 'default',
            'use_custom_templates' => 'N',
            'category_type'        => IECategoryTypes::IE_IMAGE_ENHANCER,
        ]
    );

    // Create IE Credits Product
    fn_update_product([
        'product'               => __('is2or_claid_ai.image_enhancer_credits'),
        'company_id'            => 0,
        'category_ids'         => [$category_id],
        'main_category'        => $category_id,
        'price'                => 10,
        'full_description'     => '',
        'status'               => 'H',
        'options_type'         => '',
        'exceptions_type'      => '',
        'product_code'         => '',
        'list_price'           => 0,
        'amount'               => '1',
        'zero_price_action'    => '',
        'tracking'             => '',
        'min_qty'              => null,
        'max_qty'              => null,
        'qty_step'             => null,
        'list_qty_count'       => null,
        'tax_ids'              => '',
        'usergroup_ids'        => 0,
        'avail_since'          => '',
        'out_of_stock_actions' => 'N',
        'details_layout'       => 'default',
        'short_description'    => '',
        'search_words'         => '',
        'promo_text'           => '',
        'page_title'           => '',
        'meta_description'     => '',
        'meta_keywords'        => '',
        'weight'               => 0,
        'free_shipping'        => 'Y',
        'shipping_freight'     => 0,
        'min_items_in_box'     => 0,
        'max_items_in_box'     => 0,
        'prices'               => [
            1 => [
                'lower_limit'  => '',
                'price'        => 10,
                'type'         => 'A',
                'usergroup_id' => 0,
            ],
        ],
        'product_features'     => [],
        'product_type'         => IEProductTypes::IE_CREDITS,
        'is_edp'               => 'Y',
    ]);

    Registry::set('hooks', $current_hooks, true);
}

function fn_is2or_claid_ai_uninstall() {
    fn_define("IS2OR_CLAID_AI_UNINSTALL", true);

    $product_id = fn_is2or_claid_ai_get_credits_product_id();
    fn_delete_product($product_id);

    $category_id = fn_is2or_claid_ai_get_credits_category_id();
    fn_delete_category($category_id);
}

function fn_is2or_claid_ai_get_credits_product_id() {
    return (int) db_get_field(
        "SELECT product_id FROM ?:products WHERE product_type=?s",
        IEProductTypes::IE_CREDITS
    );
}

function fn_is2or_claid_ai_get_credits_category_id() {
    return (int) db_get_field(
        "SELECT category_id FROM ?:categories WHERE category_type=?s",
        IECategoryTypes::IE_IMAGE_ENHANCER
    );
}

function fn_is2or_claid_ai_get_company_credits($company_id) {
    return (int) db_get_field("SELECT is2or_claid_credits FROM ?:companies WHERE company_id=?i", $company_id);
}

function fn_is2or_claid_ai_create_credit_log($data) {
    db_query("INSERT INTO ?:is2or_claid_credit_logs ?e", $data);
}

function fn_is2or_claid_ai_upload_image($type = 'file', $image, $tmp = false) {
    if (!in_array($type, ['file', 'url'])) {
        return false;
    }

    $company_id = (int) Registry::get('runtime.company_id');

    if ($type == 'file') {
        $filename = fn_basename($image['name']);
        $ext = fn_get_file_ext($filename);
        $unique_name = uniqid($tmp ? 'tmp_' : '', true) . '.' . $ext;

        $file_contents = file_get_contents($image['tmp_name']);
    } else {
        $unique_name = uniqid($tmp ? 'tmp_' : '', true) . '.' . ($image['ext'] ?? 'jpeg');
        $file_contents = file_get_contents($image['tmp_url']);
    }

    $storage = Storage::instance('images');
    $dir = fn_is2or_claid_ai_generate_image_dir($company_id, $tmp);
    $path = $dir . $unique_name;

    list($filesize) = $storage->put($path, [
        'contents' => $file_contents,
        'overwrite' => true
    ]);

    if ($filesize) {
        $data = [
            'company_id' => $company_id,
            'path' => $path,
            'filesize' => $filesize,
            'timestamp' => TIME,
            'is_tmp' => $tmp
        ];

        $image_id = db_query("INSERT INTO ?:is2or_claid_images ?e", $data);

        if ($image_id) {
            $data['url'] = $storage->getUrl($path);
            $data['image_id'] = $image_id;

            return $data;
        }
    }

    return false;
}

function fn_is2or_claid_ai_generate_image_dir($company_id, $tmp = false) {
    $dir = 'claid_ai/';

    if ($tmp) {
        $dir .= 'tmp/';
    }

    $dir .= $company_id . '/';

    return $dir;
}

function fn_is2or_claid_ai_delete_image($path) {
    $storage = Storage::instance('images');
    
    if ($storage->isExist($path)) {
        $storage->delete($path);
    }

    $image_id = fn_is2or_claid_ai_get_image_id_by_path($path);
    if ($image_id) {
        db_query("DELETE FROM ?:is2or_claid_images WHERE image_id = ?i", $image_id);
    }

}

function fn_is2or_claid_ai_get_image_id_by_path($path) {
    return (int) db_get_field("SELECT image_id FROM ?:is2or_claid_images WHERE `path`=?s", $path);
}

function fn_is2or_claid_ai_create_credit_logs($data) {
    db_query("INSERT INTO ?:is2or_claid_credit_logs ?e", $data);
}

function fn_is2or_claid_ai_use_credit($company_id, $cost) {
    $current_credits = fn_is2or_claid_ai_get_company_credits($company_id);
    $update_credits = $current_credits - $cost;

    db_query("UPDATE ?:companies SET is2or_claid_credits = ?i WHERE company_id = ?i", $update_credits, $company_id);

    return $update_credits;
}

function fn_is2or_claid_ai_process_image($action, $path, $prompt) {
    $source_image_id = fn_is2or_claid_ai_get_image_id_by_path($path);
    $source_image = db_get_row("SELECT * FROM ?:is2or_claid_images WHERE image_id = ?i", $source_image_id);

    $storage = Storage::instance('images');
    $url = $storage->getUrl($source_image['path']);

    $api = new Api();
    $result = $api->processImage($action, $url, $prompt);
    
    if ($result === false) {
        return false;
    }

    // Upload image
    $image = fn_is2or_claid_ai_upload_image('url', $result);
    if ($image) {
        // Craate credit log
        if (
            $company_id = (int) Registry::get('runtime.company_id')
            && $cost = Registry::ifGet('addons.is2or_claid_ai.' . $action . '_cost', 0) > 0
        ) {
            $data = [
                'company_id' => $company_id,
                'image_id' => $image['image_id'],
                'description' => __('is2or_claid_ai.' . $action),
                'timestamp' => TIME,
                'credits' => -$cost
            ];

            fn_is2or_claid_ai_create_credit_logs($data);
            // Update company credits
            $image['company_credits'] = fn_is2or_claid_ai_use_credit($company_id, $cost);
        }

        return $image;
    }
    
    return false;
}

function fn_is2or_claid_ai_get_images($company_id, $params = [], $items_per_page = 0) {
    $default_params = [
        'company_id' => $company_id,
        'page' => 1, 
        'is_tmp' => 0,
        'items_per_page' => $items_per_page
    ];
    $params = array_merge($default_params, $params);

    $fields = [
        '?:is2or_claid_images.*',
    ];

    $sortings = [
        'id' => '?:is2or_claid_images.id',
        'filesize' => '?:is2or_claid_images.filesize',
        'timestamp' => '?:is2or_claid_images.timestamp'
    ];

    $condition = $limit = '';
    
    $sorting = db_sort($params, $sortings, 'timestamp', 'desc');

    $condition .= db_quote(" AND ?:is2or_claid_images.company_id = ?i", $company_id);

    if (isset($params['is_tmp'])) {
        $condition .= db_quote(" AND ?:is2or_claid_images.is_tmp = ?i", $params['is_tmp']);
    }

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:is2or_claid_images WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $images = db_get_hash_array(
        "SELECT ?p FROM ?:is2or_claid_images WHERE 1 ?p ?p ?p",
        'image_id',
        implode(', ', $fields),
        $condition,
        $sorting,
        $limit
    );

    $storage = Storage::instance('images');
    foreach ($images as &$image) {
        $image['url'] = $storage->getUrl($image['path']);
    }

    return [$images, $params];
}

function fn_is2or_claid_ai_get_buy_credit_url($company_id, $auth, $amount) {
    if ($auth['user_type'] === UserTypes::ADMIN) {
        $user_id = fn_get_company_admin_user_id($company_id);
    } else {
        $user_id = $auth['user_id'];
    }

    $url_params = [];
    if (!empty($amount)) {
        $url_params['currency'] = CART_SECONDARY_CURRENCY;
        $url_params['credit_amount'] = $amount;
    }

    $buy_credit_url = Url::buildUrn(
        ['is2or_claid_ai', 'buy_credit'],
        $url_params
    );

    $buy_credit_url = Url::buildUrn(['profiles', 'act_as_user'], [
        'area'         => 'C',
        'user_id'      => $user_id,
        'redirect_url' => $buy_credit_url,
    ]);

    return $buy_credit_url;
}

function fn_is2or_claid_ai_is_tryon_category($category_id) {
    return db_get_field("SELECT is2or_tryon_enabled FROM ?:categories WHERE category_id=?i", $category_id) == 'Y';
}

function fn_is2or_claid_ai_get_garments($product_id) {
    $garments =  db_get_hash_array("SELECT * FROM ?:is2or_product_garments WHERE product_id=?i ORDER BY position ASC", 'garment_id', $product_id);

    foreach ($garments as &$garment) {
        $garment['main_pair'] = fn_get_image_pairs($garment['garment_id'], 'is2or_tryon_garment', 'M');
    }
    unset($garment);

    return $garments;
}

function fn_is2or_claid_ai_get_garment($garment_id) {
    $data = db_get_row("SELECT * FROM ?:is2or_product_garments WHERE garment_id=?i", $garment_id);

    if ($data) {
        $data['main_pair'] = fn_get_image_pairs($garment_id, 'is2or_tryon_garment', 'M');
    }

    return $data;
}

function fn_is2or_claid_ai_attach_image_pair($name, $object_id) {
    $pair_ids = fn_attach_image_pairs($name, $name, $object_id);

    return $pair_ids ? reset($pair_ids) : 0;
}

function fn_is2or_claid_ai_update_garment($garment_id, $garment_data) {
    if ($garment_id) {
        $old_data = fn_is2or_claid_ai_get_garment($garment_id);

        $pair_id = fn_is2or_claid_ai_attach_image_pair('is2or_tryon_garment', $garment_id);
        if ($pair_id && $pair_id != $old_data['pair_id']) {
            $garment_data['pair_id'] = $pair_id;
        }

        db_query("UPDATE ?:is2or_product_garments SET ?u WHERE garment_id = ?i", $garment_data, $garment_id);
    } else {
        $garment_id = db_query("INSERT INTO ?:is2or_product_garments ?e", $garment_data);

        $pair_id = fn_is2or_claid_ai_attach_image_pair('is2or_tryon_garment', $garment_id);
        if ($pair_id) {
            db_query("UPDATE ?:is2or_product_garments SET pair_id = ?i WHERE garment_id = ?i", $pair_id, $garment_id);
        }
    }

    return $garment_id;
}

function fn_is2or_claid_ai_delete_garment($garment_id) {
    $garment = fn_is2or_claid_ai_get_garment($garment_id);

    if ($garment['pair_id']) {
        fn_delete_image_pairs($garment['pair_id'], 'is2or_tryon_garment');
    }

    return db_query("DELETE FROM ?:is2or_product_garments WHERE garment_id = ?i", $garment_id);
}

function fn_is2or_claid_ai_get_models($user_id) {
    $models = db_get_hash_array("SELECT * FROM ?:is2or_tryon_models WHERE user_id=?i", 'model_id', $user_id);

    foreach ($models as &$model) {
        $model['main_pair'] = fn_get_image_pairs($model['model_id'], 'is2or_tryon_model', 'M');
    }
    unset($model);

    return $models;
}

function fn_is2or_claid_ai_add_model($model_data) {
    $model_id = db_query("INSERT INTO ?:is2or_tryon_models ?e", $model_data);

    $pair_id = fn_is2or_claid_ai_attach_image_pair('is2or_tryon_model', $model_id);
    if ($pair_id) {
        db_query("UPDATE ?:is2or_tryon_models SET pair_id = ?i WHERE model_id = ?i", $pair_id, $model_id);
    }

    return $model_id;
}

function fn_is2or_claid_ai_get_model($model_id) {
    $model = db_get_row("SELECT * FROM ?:is2or_tryon_models WHERE model_id=?i", $model_id);

    if ($model) {
        $model['main_pair'] = fn_get_image_pairs($model['model_id'], 'is2or_tryon_model', 'M');
    }

    return $model;
}

function fn_is2or_claid_ai_delete_model($mdoel_id) {
    $model = fn_is2or_claid_ai_get_model($mdoel_id);

    if ($model['pair_id']) {
        fn_delete_image_pairs($model['pair_id'], 'is2or_tryon_model');
    }

    return db_query("DELETE FROM ?:is2or_tryon_models WHERE model_id = ?i", $mdoel_id);
}

function fn_is2or_claid_ai_get_tryon_results($params = []) {
    if (empty($params)) {
        return [];
    }

    $condition = '';

    if (!empty($params['user_id'])) {
        $condition .= db_quote(" AND user_id=?i", $params['user_id']);
    }

    if (!empty($params['model_id'])) {
        $condition .= db_quote(" AND model_id=?i", $params['model_id']);
    }

    if (!empty($params['product_id'])) {
        $condition .= db_quote(" AND product_id=?i", $params['product_id']);
    }

    if (!empty($params['garmets_ids'])) {
        $garment_ids = is_array($params['garment_ids']) ? implode(',', $params['garment_ids']) : $params['garment_ids'];
        $condition .= db_quote(" AND garment_ids = ?s", $garment_ids);
    }

    $data = db_get_hash_array("SELECT * FROM ?:is2or_tryon_results WHERE 1 ?p ORDER BY timestamp DESC", 'result_id', $condition);

    return $data;
}

function fn_is2or_claid_ai_generate_tryon($product_id, $model_id) {
    $auth = Tygh::$app['session']['auth'];

    $garments = fn_is2or_claid_ai_get_garments($product_id);
    $garment_ids = array_column($garments, 'garment_id');

    $result = db_get_row(
        "SELECT * FROM ?:is2or_tryon_results WHERE user_id=?i AND model_id=?i AND product_id=?i AND garment_ids = ?s",
        $auth['user_id'], $model_id, $product_id, implode(',', $garment_ids)
    );
    
    if ($result) {
        return fn_is2or_claid_ai_get_tryon_result($result['result_id']);
    }

    $model = fn_is2or_claid_ai_get_model($model_id);
    $garment = fn_is2or_claid_ai_get_garments($product_id);

    $model_url = $model['main_pair']['detailed']['image_path'] ?? '';

    $clothing_urls = [];
    foreach ($garment as $garment) {
        $garment_url = $garment['main_pair']['detailed']['image_path'] ?? '';
        if ($garment_url) {
            $clothing_urls[] = $garment_url;
        }
    }

    if (empty($model) || empty($garment) || empty($model_url) || empty($garment_url)) {
        return false;
    }

    $api = new Api();
    $result = $api->tryon($model_url, $clothing_urls);

    if ($result !== false && !empty($result['result']['output_objects'])) {
        $image_data = file_get_contents($result['result']['output_objects'][0]['tmp_url']);

        $storage = Storage::instance('images');
        $path = 'is2or_tryon/result/'. $auth['user_id'] . '/' . $product_id . '/' . time() . '.jpg';
        $storage->put($path, [
            'contents' => $image_data,
            'overwrite' => true
        ]);

        // Create result
        $result_id = db_query("INSERT INTO ?:is2or_tryon_results ?e", [
           'user_id' => $auth['user_id'],
           'product_id' => $product_id,
           'garment_ids' => implode(',', $garment_ids),
           'model_id' => $model_id,
           'path' => $path,
           'timestamp' => TIME
        ]);

        return fn_is2or_claid_ai_get_tryon_result($result_id);
    }

    return false;
}

function fn_is2or_claid_ai_get_tryon_result($result_id) {
    return db_get_row("SELECT * FROM ?:is2or_tryon_results WHERE result_id=?i", $result_id);
}

/* HOOKS FUNCTIONS */
function fn_is2or_claid_ai_update_company_pre($company_data, $company_id, $lang_code, $can_update) {
    if ($can_update && isset($company_data['is2or_claid_credits'])) {
        $newCreditsValue = $company_data['is2or_claid_credits'];
        $oldCreditsValue = fn_is2or_claid_ai_get_company_credits($company_id);

        // Create credit logs
        $updateValue = $newCreditsValue - $oldCreditsValue;
        if ($updateValue != 0) {
            if ($updateValue > 0) {
                $description = __('is2or_claid_ai.credits_added_by_admin', ['[credit]' => $updateValue]);
            } else {
                $description = __('is2or_claid_ai.credits_deducted_by_admin', ['[credit]' => abs($updateValue)]);
            }

            $data = [
                'company_id' => $company_id,
                'description' => $description,
                'timestamp' => TIME,
                'credits' => $updateValue
            ];

            fn_is2or_claid_ai_create_credit_log($data);
        }
    }
}

function fn_is2or_claid_ai_delete_product_pre($product_id, &$status) {
    if (
        $product_id == fn_is2or_claid_ai_get_credits_product_id()
        && !defined("IS2OR_CLAID_AI_UNINSTALL")
    ) {
        $status = false;
    }
}

function fn_is2or_claid_ai_delete_category_pre(&$category_id, $recurse) {
    if (
        $category_id != fn_is2or_claid_ai_get_credits_category_id()
        || !defined("IS2OR_CLAID_AI_UNINSTALL")
    ) {
        return;
    }

    $category_id = null;
}

function fn_is2or_claid_ai_get_products($params, $fields, $sortings, &$condition) {
    if (empty($params['pid'])) {
        $condition .= db_quote(" AND products.product_type != ?s", IEProductTypes::IE_CREDITS);
    }
}

function fn_is2or_claid_ai_get_product_data_post(&$product_data) {
    if (!empty($product_data['product_type']) && $product_data['product_type'] == IEProductTypes::IE_CREDITS) {
        $product_data['zero_price_action'] = ProductZeroPriceActions::NOT_ALLOW_ADD_TO_CART;
    }

    // Check Tryon Enabled
    $tryon_enabled = false;
    foreach ($product_data['category_ids'] as $category_id) {
        if (fn_is2or_claid_ai_is_tryon_category($category_id)) {
            $tryon_enabled = true;
            break;
        }
    }
    $garments = [];
    if ($tryon_enabled) {
        $garments = fn_is2or_claid_ai_get_garments($product_data['product_id']);
    }

    $product_data['garments'] = $garments;
    $product_data['tryon_enabled'] = $tryon_enabled;
}

function fn_is2or_claid_ai_smarty_component_configurable_page_field_before_output(
    $entity,
    $tab,
    $section,
    $field,
    array &$field_config,
    array $params,
    $content,
    $template
) {
    if ($entity == 'products') {
        $product_data = $template->getTemplateVars('product_data');

        if (
            !empty($product_data['product_id'])
            && $product_data['product_id'] == fn_is2or_claid_ai_get_credits_product_id()
        ) {
            $field_config['is_optional'] = $field !== 'product' && $field !== 'images';
            $field_config['is_visible'] = !$field_config['is_optional'];
        }
    }

    if ($entity == 'categories') {
        $category_data = $template->getTemplateVars('category_data');

        if (
            !empty($category_data['category_id'])
            && $category_data['category_id'] == fn_is2or_claid_ai_get_credits_category_id()
        ) {
            $field_config['is_optional'] = $field !== 'category' && $field !== 'images';
            $field_config['is_visible'] = !$field_config['is_optional'];
        }
    }
}

function fn_is2or_claid_ai_smarty_component_configurable_page_section_before_output(
    $entity,
    $tab,
    $section,
    array &$section_config,
    array $params,
    $content,
    $template
) {
    if ($entity == 'products') {
        $product_data = $template->getTemplateVars('product_data');

        if (
            !empty($product_data['product_id'])
            && $product_data['product_id'] == fn_is2or_claid_ai_get_credits_product_id()
        ) {
            $section_config['is_optional'] = $section !== 'information';
            $section_config['is_visible'] = $section === 'information';
        }
    }

    if ($entity == 'categories') {
        $category_data = $template->getTemplateVars('category_data');

        if (
            !empty($category_data['category_id'])
            && $category_data['category_id'] == fn_is2or_claid_ai_get_credits_category_id()
        ) {
            $section_config['is_optional'] = $section !== 'information';
            $section_config['is_visible'] = $section === 'information';
        }
    }
}

function fn_is2or_claid_ai_calculate_cart_post($cart, $auth, $calculate_shipping, $calculate_taxes, $options_style, $apply_cart_promotions, &$cart_products, $product_groups) {
    foreach ($cart_products as $key => &$cart_product) {
        $original_cart_item = $cart['products'][$key];
        if (isset($original_cart_item['extra']['is2or_claid_ai'])) {
            $cart_product['product'] .= ' (' . $original_cart_item['extra']['is2or_claid_ai']['credit_amount'] . ' credits)';
        }
    }
}

function fn_is2or_claid_ai_get_order_items_info_post(&$order, $v, $k) {
    // fn_print_die($order['products'][$k]['product']);
    if (isset($v['extra']['is2or_claid_ai'])) {
        $order['products'][$k]['product'] .= ' (' . $v['extra']['is2or_claid_ai']['credit_amount'] . ' credits)';
    }
}

function fn_is2or_claid_ai_allow_place_order_post($cart, $auth, $parent_order_id, $total, &$result) {
    if (!$result && !empty($cart['is_is2or_buy_credit'])) {
        $result = true;
    }
}

function fn_is2or_claid_ai_change_order_status($status_to, $status_from, $order_info) {
    if (!in_array($status_to, fn_get_settled_order_statuses())) {
        return;
    }

    foreach ($order_info['products'] as $product) {
        if (!isset($product['extra']['is2or_claid_ai'])) {
            continue;
        }

        $vendor_id = $product['extra']['is2or_claid_ai']['vendor_id'];
        $amount = $product['extra']['is2or_claid_ai']['credit_amount'];

        // create log
        $log_data = [
            'company_id' => $vendor_id,
            'image_id' => 0,
            'description' => "Order #{$order_info['order_id']}",
            'timestamp' => TIME,
            'credits' => $amount
        ];
        fn_is2or_claid_ai_create_credit_log($log_data);

        db_query("UPDATE ?:companies SET is2or_claid_credits = is2or_claid_credits + ?i WHERE company_id = ?i", $amount, $vendor_id);
    }
}
/* HOOKS FUNCTIONS */