<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Http;

$ajax_modes = ['request_authorize_url', 'trigger_import', 'sync_history', 'request_authorize_url_zid', 'trigger_import_zid', 'sync_history_zid', 'generate_category', 'generate_category_zid', 'request_authorize_url_noon', 'trigger_import_noon', 'sync_history_noon', 'generate_category_noon'];

if (in_array($mode, $ajax_modes, true)) {
    if (!defined('AJAX_REQUEST')) {
        define('AJAX_REQUEST', true);
    }
}

// Get default company_id from active admin/vendor session
$company_id = fn_get_runtime_company_id();

// ====================================================================
// 1. MODE: Request Authorize URL
// ====================================================================
if ($mode == 'request_authorize_url') {
    header('Content-Type: application/json');

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');
    $return_url = fn_url("is2or_channel_integrations.callback_result", "A", "https");

    $payload = json_encode([
        'company_id' => (int) $company_id,
        'return_url' => $return_url
    ]);

    $service_id = 'local-test';
    $hmac_key = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    
    $body_hash = hash('sha256', $payload);
    $message = $service_id . "\n"
             . $timestamp . "\n"
             . $nonce . "\n"
             . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: ' . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: ' . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: ' . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $response = Http::post(
        $sync_service_url . '/internal/platforms/salla/authorize-url',
        $payload,
        $extra_options
    );

    if (!is_string($response) || trim($response) === '') {
        echo json_encode([
            'error'   => true,
            'message' => 'No response received from integration server.'
        ]);
        exit;
    }

    $res_data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($res_data)) {
        echo json_encode([
            'error'   => true,
            'message' => 'Invalid response from integration server.',
            'debug'   => substr(strip_tags($response), 0, 200)
        ]);
        exit;
    }

    if (isset($res_data['authorization_url'])) {
        echo json_encode(['authorization_url' => $res_data['authorization_url']]);
    } else {
        echo json_encode([
            'error'   => true,
            'message' => isset($res_data['message']) ? $res_data['message'] : 'Failed to get authorization URL.'
        ]);
    }

    exit;
}

// ====================================================================
// 2. MODE: Manage
// ====================================================================
if ($mode == 'manage') {

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');
    $service_id       = 'local-test';
    $hmac_key         = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id           = 'key-v1';

    // ==========================================
    // Salla status check
    // ==========================================
    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'salla'",
        $company_id
    );

    if (!empty($connection) && $connection['status'] === 'active') {
        
        $connection_id = (int) $connection['connection_id'];

        $timestamp = (string) time();
        $nonce     = bin2hex(random_bytes(16));
        $body_hash = hash('sha256', '');
        $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
        $signature = hash_hmac('sha256', $message, $hmac_key);

        $extra_options = [
            'headers' => [
                'Content-Type: application/json',
                'X-IS2OR-Service: '   . $service_id,
                'X-IS2OR-Timestamp: ' . $timestamp,
                'X-IS2OR-Nonce: '     . $nonce,
                'X-IS2OR-Signature: ' . $signature,
                'X-IS2OR-Key-ID: '    . $key_id
            ],
            'ch_options' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]
        ];

        $response = Http::post(
            $sync_service_url . '/internal/connections/' . $connection_id . '/refresh-token-check',
            [],
            $extra_options
        );

        $res_data = json_decode($response, true);

        if (is_string($response) && !empty($response) && json_last_error() === JSON_ERROR_NONE && is_array($res_data)) {
            if (isset($res_data['live']) && $res_data['live'] === false) {
                db_query("DELETE FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'salla'", $company_id);
                db_query("DELETE FROM ?:is2or_channel_sync_history WHERE company_id = ?i AND platform = 'salla'", $company_id);
                fn_set_notification('W', __('warning'), 'Your Salla connection has been disconnected from the integration server. Please reconnect.');
                $salla_status = 'disconnected';
            } else {
                $salla_status = $connection['status'];
            }
        } else {
            $salla_status = $connection['status'];
        }

    } else {
        $salla_status = !empty($connection['status']) ? $connection['status'] : 'disconnected';
    }

    // ==========================================
    // Zid status check
    // ==========================================
    $zid_connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'zid'",
        $company_id
    );

    if (!empty($zid_connection) && $zid_connection['status'] === 'active') {

        $zid_connection_id = (int) $zid_connection['connection_id'];

        $timestamp = (string) time();
        $nonce     = bin2hex(random_bytes(16));
        $body_hash = hash('sha256', '');
        $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
        $signature = hash_hmac('sha256', $message, $hmac_key);

        $extra_options_zid = [
            'headers' => [
                'Content-Type: application/json',
                'X-IS2OR-Service: '   . $service_id,
                'X-IS2OR-Timestamp: ' . $timestamp,
                'X-IS2OR-Nonce: '     . $nonce,
                'X-IS2OR-Signature: ' . $signature,
                'X-IS2OR-Key-ID: '    . $key_id
            ],
            'ch_options' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]
        ];

        $zid_response = Http::post(
            $sync_service_url . '/internal/connections/' . $zid_connection_id . '/refresh-token-check', 
            [],
            $extra_options_zid
        );
        
        $zid_res_data = json_decode($zid_response, true);

        if (is_string($zid_response) && !empty($zid_response) && json_last_error() === JSON_ERROR_NONE && is_array($zid_res_data)) {
            if (isset($zid_res_data['live']) && $zid_res_data['live'] === false) {
                db_query("DELETE FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'zid'", $company_id);
                db_query("DELETE FROM ?:is2or_channel_sync_history WHERE company_id = ?i AND platform = 'zid'", $company_id);
                fn_set_notification('W', __('warning'), 'Your Zid connection has been disconnected from the integration server. Please reconnect.');
                $zid_status = 'disconnected';
            } else {
                $zid_status = $zid_connection['status'];
            }
        } else {
            $zid_status = $zid_connection['status'];
        }

    } else {
        $zid_status = !empty($zid_connection['status']) ? $zid_connection['status'] : 'disconnected';
    }

    // ==========================================
    // Noon status check
    // ==========================================
    $noon_connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'noon'",
        $company_id
    );

    if (!empty($noon_connection) && $noon_connection['status'] === 'active') {

        $noon_connection_id = (int) $noon_connection['connection_id'];

        $timestamp = (string) time();
        $nonce     = bin2hex(random_bytes(16));
        $body_hash = hash('sha256', '');
        $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
        $signature = hash_hmac('sha256', $message, $hmac_key);

        $extra_options_noon = [
            'headers' => [
                'Content-Type: application/json',
                'X-IS2OR-Service: '   . $service_id,
                'X-IS2OR-Timestamp: ' . $timestamp,
                'X-IS2OR-Nonce: '     . $nonce,
                'X-IS2OR-Signature: ' . $signature,
                'X-IS2OR-Key-ID: '    . $key_id
            ],
            'ch_options' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]
        ];

        $noon_response = Http::post(
            $sync_service_url . '/internal/connections/' . $noon_connection_id . '/refresh-token-check',
            [],
            $extra_options_noon
        );

        $noon_res_data = json_decode($noon_response, true);

        if (is_string($noon_response) && !empty($noon_response) && json_last_error() === JSON_ERROR_NONE && is_array($noon_res_data)) {
            if (isset($noon_res_data['live']) && $noon_res_data['live'] === false) {
                db_query("DELETE FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'noon'", $company_id);
                db_query("DELETE FROM ?:is2or_channel_sync_history WHERE company_id = ?i AND platform = 'noon'", $company_id);
                fn_set_notification('W', __('warning'), 'Your Noon connection has been disconnected from the integration server. Please reconnect.');
                $noon_status = 'disconnected';
            } else {
                $noon_status = $noon_connection['status'];
            }
        } else {
            $noon_status = $noon_connection['status'];
        }

    } else {
        $noon_status = !empty($noon_connection['status']) ? $noon_connection['status'] : 'disconnected';
    }

    $return_url = fn_url("is2or_channel_integrations.callback_result", "A", "https");

    Tygh::$app['view']->assign('salla_status', $salla_status);
    Tygh::$app['view']->assign('zid_status', $zid_status);
    Tygh::$app['view']->assign('noon_status', $noon_status);
    Tygh::$app['view']->assign('sync_service_url', $sync_service_url);
    Tygh::$app['view']->assign('company_id', $company_id);
    Tygh::$app['view']->assign('return_url', $return_url);
}

// ====================================================================
// 3. MODE: Callback Result
// ====================================================================
if ($mode == 'callback_result') {
    $status    = !empty($_REQUEST['status']) ? $_REQUEST['status'] : 'error';
    $error_msg = !empty($_REQUEST['error_description']) ? $_REQUEST['error_description'] : '';
    $platform  = !empty($_REQUEST['platform']) ? $_REQUEST['platform'] : 'salla';

    $target_company_id = !empty($_REQUEST['company_id']) ? (int) $_REQUEST['company_id'] : $company_id;
    $connection_id     = !empty($_REQUEST['connection_id']) ? (int) $_REQUEST['connection_id'] : 0;

    $platform_label = strtoupper($platform); // 'SALLA' atau 'ZID'

    if ($status === 'success' && !empty($target_company_id)) {
        db_query(
            "INSERT INTO ?:is2or_channel_connections
                (connection_id, company_id, platform, status, created_at)
             VALUES
                (?i, ?i, ?s, 'active', ?i)
             ON DUPLICATE KEY UPDATE
                connection_id = VALUES(connection_id),
                status = 'active'",
            $connection_id,
            $target_company_id,
            $platform,
            time()
        );

        fn_set_notification('N', __('notice'), 'Your ' . $platform_label . ' store has been connected successfully!');

    } else {
        $msg = !empty($error_msg) ? $error_msg : $platform_label . ' authorization was cancelled or failed.';
        fn_set_notification('E', __('error'), $msg);
    }

    return [CONTROLLER_STATUS_REDIRECT, 'is2or_channel_integrations.manage'];
}

// ====================================================================
// HELPER: Build signed internal API headers (HMAC), reused across requests
// ====================================================================
if (!function_exists('fn_is2or_build_internal_extra_options')) {
    function fn_is2or_build_internal_extra_options($payload, $hmac_key)
    {
        $service_id = 'local-test';
        $key_id     = 'key-v1';

        $timestamp = (string) time();
        $nonce     = bin2hex(random_bytes(16));

        $body_hash = hash('sha256', $payload);
        $message = $service_id . "\n"
                 . $timestamp . "\n"
                 . $nonce . "\n"
                 . $body_hash;
        $signature = hash_hmac('sha256', $message, $hmac_key);

        return [
            'headers' => [
                'Content-Type: application/json',
                'X-IS2OR-Service: ' . $service_id,
                'X-IS2OR-Timestamp: ' . $timestamp,
                'X-IS2OR-Nonce: ' . $nonce,
                'X-IS2OR-Signature: ' . $signature,
                'X-IS2OR-Key-ID: ' . $key_id
            ],
            'ch_options' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]
        ];
    }
}

// ====================================================================
// HELPER: Apply a single category result to a product (set as Main category)
// ====================================================================
if (!function_exists('fn_is2or_apply_category_to_product')) {
    function fn_is2or_apply_category_to_product($product_id, $category_id, $company_id)
    {
        // Storefront IS2OR (storefront_id = 1).
        $is2or_storefront_id = 1;

        $product_id  = (int) $product_id;
        $category_id = (int) $category_id;
        $company_id  = (int) $company_id;

        $category = db_get_row(
            "SELECT category_id, storefront_id, company_id FROM ?:categories WHERE category_id = ?i",
            $category_id
        );

        if (empty($category)) {
            return false;
        }

        if ((int) $category['storefront_id'] !== $is2or_storefront_id
            && (int) $category['storefront_id'] !== 0
        ) {
            return false;
        }

        $product_data = [
            'category_ids'  => [$category_id],
            'main_category' => $category_id,
            'company_id'    => $company_id,
        ];

        fn_update_product_categories($product_id, $product_data, false, $category['company_id']);

        return true;
    }
}

// ====================================================================
// HELPER: Call /features/categorize/bulk, poll until done, then apply categories
// ====================================================================
if (!function_exists('fn_is2or_run_bulk_categorization')) {
    function fn_is2or_run_bulk_categorization($connection_id, $company_id, array &$debug_log = [])
    {
        $debug_log[] = '[bulk] START connection_id=' . $connection_id . ' company_id=' . $company_id;

        $product_ids = db_get_fields(
            "SELECT DISTINCT product_id FROM ?:is2or_channel_product_mappings WHERE connection_id = ?i AND company_id = ?i",
            (int) $connection_id,
            (int) $company_id
        );
        $debug_log[] = '[bulk] raw product_ids: ' . var_export($product_ids, true);

        $product_ids = array_values(array_unique(array_map('intval', $product_ids)));
        $debug_log[] = '[bulk] cleaned product_ids: ' . implode(',', $product_ids);

        if (empty($product_ids)) {
            $debug_log[] = '[bulk] ABORT: no product_ids found';
            return;
        }

        $descriptions = db_get_hash_single_array(
            "SELECT product_id, product FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code = ?s",
            ['product_id', 'product'],
            $product_ids,
            CART_LANGUAGE
        );
        $debug_log[] = '[bulk] descriptions count: ' . count($descriptions) . ' | CART_LANGUAGE=' . CART_LANGUAGE;

        $products_payload = [];
        foreach ($product_ids as $pid) {
            if (isset($descriptions[$pid]) && $descriptions[$pid] !== '') {
                $products_payload[] = ['product_id' => $pid, 'name' => $descriptions[$pid]];
            }
        }
        $debug_log[] = '[bulk] products_payload count: ' . count($products_payload);

        if (empty($products_payload)) {
            $debug_log[] = '[bulk] ABORT: products_payload empty (no matching descriptions)';
            return;
        }

        $category_api_base  = Registry::get('addons.is2or_channel_integrations.category_api_base_url');
        $category_api_token = Registry::get('addons.is2or_channel_integrations.category_api_token');
        $debug_log[] = '[bulk] category_api_base=' . var_export($category_api_base, true) . ' token_set=' . (empty($category_api_token) ? 'NO' : 'YES');

        $bulk_payload = json_encode([
            'review_required' => false,
            'storefront_id'   => 1,
            'products'        => $products_payload
        ]);
        $debug_log[] = '[bulk] bulk_payload length=' . strlen($bulk_payload) . ' json_last_error=' . json_last_error_msg();
        $debug_log[] = '[bulk] bulk_payload preview: ' . substr($bulk_payload, 0, 800);

        $bulk_extra_options = [
            'headers' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $category_api_token
            ],
            'ch_options' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]
        ];

        $debug_log[] = '[bulk] POST -> ' . $category_api_base . '/features/categorize/bulk';
        $bulk_response = Http::post(
            $category_api_base . '/features/categorize/bulk',
            $bulk_payload,
            $bulk_extra_options
        );
        $debug_log[] = '[bulk] bulk_response raw: ' . var_export($bulk_response, true);

        if (!is_string($bulk_response) || trim($bulk_response) === '') {
            $debug_log[] = '[bulk] ABORT: bulk_response kosong/tidak valid (timeout, SSL, atau URL salah)';
            return;
        }

        $bulk_data = json_decode($bulk_response, true);
        $debug_log[] = '[bulk] bulk_data decoded: ' . var_export($bulk_data, true) . ' | json_last_error=' . json_last_error_msg();

        if (!is_array($bulk_data) || empty($bulk_data['job_id'])) {
            $debug_log[] = '[bulk] ABORT: bulk_data invalid atau job_id kosong';
            return;
        }

        $categorize_job_id = $bulk_data['job_id'];
        $debug_log[] = '[bulk] job_id=' . $categorize_job_id;

        $max_attempts = 300;
        $attempt      = 0;
        $status_data  = null;

        $get_extra_options = [
            'headers' => ['Authorization: Bearer ' . $category_api_token],
            'ch_options' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]
        ];

        while ($attempt < $max_attempts) {
            $attempt++;
            sleep(1);

            $status_response = Http::get(
                $category_api_base . '/features/categorize/bulk/' . $categorize_job_id,
                '',
                $get_extra_options
            );
            $debug_log[] = '[bulk] poll #' . $attempt . ' status_response: ' . var_export($status_response, true);

            if (!is_string($status_response) || trim($status_response) === '') {
                $debug_log[] = '[bulk] ABORT: status_response kosong pada attempt ' . $attempt;
                return;
            }

            $status_data = json_decode($status_response, true);
            $debug_log[] = '[bulk] poll #' . $attempt . ' decoded: ' . var_export($status_data, true) . ' | json_last_error=' . json_last_error_msg();

            if (!is_array($status_data) || !isset($status_data['status'])) {
                $debug_log[] = '[bulk] ABORT: status_data invalid pada attempt ' . $attempt;
                return;
            }

            if ($status_data['status'] !== 'running') {
                $debug_log[] = '[bulk] job selesai status=' . $status_data['status'] . ' setelah ' . $attempt . ' attempt';
                break;
            }
        }

        if ($attempt >= $max_attempts) {
            $debug_log[] = '[bulk] WARNING: mencapai max_attempts, job mungkin masih running';
        }

        if (empty($status_data) || $status_data['status'] !== 'done' || empty($status_data['results'])) {
            $debug_log[] = '[bulk] ABORT: status akhir bukan done atau results kosong. status=' . ($status_data['status'] ?? 'NULL');
            return;
        }

        $debug_log[] = '[bulk] results count=' . count($status_data['results']);

        foreach ($status_data['results'] as $result) {
            $debug_log[] = '[bulk] result: ' . var_export($result, true);

            if (empty($result['product_id']) || empty($result['category_id'])) {
                $debug_log[] = '[bulk] SKIP: product_id/category_id kosong';
                continue;
            }
            if (!empty($result['error'])) {
                $debug_log[] = '[bulk] SKIP: error=' . var_export($result['error'], true);
                continue;
            }
            if (isset($result['status']) && $result['status'] !== 'auto_applied') {
                $debug_log[] = '[bulk] SKIP: status=' . $result['status'] . ' (bukan auto_applied)';
                continue;
            }

            $debug_log[] = '[bulk] APPLYING product_id=' . $result['product_id'] . ' category_id=' . $result['category_id'];
            fn_is2or_apply_category_to_product($result['product_id'], $result['category_id'], $company_id);
        }

        $debug_log[] = '[bulk] DONE';
    }
}

// ====================================================================
// HELPER: Poll the import job until it succeeds, then trigger bulk categorization
// ====================================================================
if (!function_exists('fn_is2or_poll_import_then_categorize')) {
    function fn_is2or_poll_import_then_categorize($job_id, $connection_id, $company_id, $sync_service_url, $hmac_key)
    {
        $max_attempts   = 150; // safety cap (~5 minutes at 2s interval)
        $attempt        = 0;
        $import_status  = null;

        while ($attempt < $max_attempts) {
            $attempt++;

            $extra_options = fn_is2or_build_internal_extra_options('', $hmac_key);

            $api_response = Http::get(
                $sync_service_url . '/internal/imports/' . (int) $job_id,
                '',
                $extra_options
            );

            if (!is_string($api_response) || trim($api_response) === '') {
                return;
            }

            $import_data = json_decode($api_response, true);

            if (!is_array($import_data) || !isset($import_data['status'])) {
                return;
            }

            $import_status = $import_data['status'];

            if ($import_status === 'succeeded') {
                break;
            }

            sleep(2);
        }

        if ($import_status !== 'succeeded') {
            return;
        }

        // Import succeeded -> run auto-categorization for all mapped products
        fn_is2or_run_bulk_categorization($connection_id, $company_id, $sync_service_url, $hmac_key);
    }
}

// ====================================================================
// 4. MODE: Trigger Import
// ====================================================================
if ($mode == 'trigger_import') {
    header('Content-Type: application/json');

    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'salla'",
        $company_id
    );

    if (empty($connection) || $connection['status'] !== 'active') {
        echo json_encode([
            'error'   => true,
            'message' => 'Vendor is not connected to Salla channel.'
        ]);
        exit;
    }

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $payload = json_encode([
        'connection_id' => (int) $connection['connection_id'],
        'company_id'    => (int) $company_id,
        'import_type'   => 'products'
    ]);

    $service_id = 'local-test';
    $hmac_key = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    
    $body_hash = hash('sha256', $payload);
    $message = $service_id . "\n"
             . $timestamp . "\n"
             . $nonce . "\n"
             . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: ' . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: ' . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: ' . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $response = Http::post(
        $sync_service_url . '/internal/imports',
        $payload,
        $extra_options
    );

    if (!is_string($response) || trim($response) === '') {
        echo json_encode([
            'error'   => true,
            'message' => 'No response received from integration server.',
            'debug'   => 'Empty or non-string response from Http::post'
        ]);
        exit;
    }

    $res_data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($res_data)) {
        echo json_encode([
            'error'   => true,
            'message' => 'Invalid response from integration server. Possible Cloudflare block.',
            'debug'   => substr(strip_tags($response), 0, 200)
        ]);
        exit;
    }

    if (isset($res_data['job_id'])) {
        $insert_data = [
            'company_id'   => (int) $company_id,
            'platform' => 'salla',
            'job_id'       => (string) $res_data['job_id'],
            'status'       => (string) $res_data['status'],
            'created_at'   => time()
        ];

        db_query("INSERT INTO ?:is2or_channel_sync_history ?e", $insert_data);

        // ----------------------------------------------------------------
        // Send the response to the user immediately, then continue running
        // the import-status polling + auto-categorization in the background
        // so the user does not have to wait for it.
        // ----------------------------------------------------------------
        echo json_encode([
            'error'   => false,
            'job_id'  => $res_data['job_id'],
            'message' => 'Data synchronization has been triggered successfully. Products will be imported gradually in the background.'
        ]);

        // // CS-Cart uses nested output buffering, so we must loop to clear ALL levels,
        // // not just one. Only after all levels are cleared will fastcgi_finish_request()
        // // reliably flush the response body to the client.
        // while (ob_get_level() > 0) {
        //     ob_end_clean();
        // }

        // header('Content-Type: application/json');
        // header('Content-Length: ' . strlen($response_body));
        // header('Connection: close');

        // echo $response_body;

        // if (function_exists('fastcgi_finish_request')) {
        //     fastcgi_finish_request();
        // } else {
        //     flush();
        // }

        // // Make sure the background process is not cut off by PHP/web server timeouts
        // if (function_exists('set_time_limit')) {
        //     set_time_limit(0);
        // }
        // ignore_user_abort(true);

        // fn_is2or_poll_import_then_categorize(
        //     $res_data['job_id'],
        //     $connection['connection_id'],
        //     $company_id,
        //     $sync_service_url,
        //     $hmac_key
        // );
    } else {
        echo json_encode([
            'error'   => true,
            'message' => 'Failed to trigger import: ' . (isset($res_data['message']) ? $res_data['message'] : 'Unknown API error')
        ]);
    }

    exit;
}

// ====================================================================
// 5. MODE: Sync History
// ====================================================================
if ($mode == 'sync_history') {
    header('Content-Type: application/json');

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $histories = db_get_array(
        "SELECT id, job_id, status, attempt_count, success_count, failure_count, completed_at, created_at
         FROM ?:is2or_channel_sync_history
         WHERE company_id = ?i AND platform = 'salla'
         ORDER BY created_at DESC",
        $company_id
    );

    if (empty($histories)) {
        echo json_encode(['error' => false, 'data' => []]);
        exit;
    }

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    foreach ($histories as &$row) {
        if ($row['status'] === 'pending' || $row['status'] === 'leased') {

            $timestamp  = (string) time();
            $nonce      = bin2hex(random_bytes(16));
            $body_hash  = hash('sha256', '');

            $message = $service_id . "\n"
                    . $timestamp . "\n"
                    . $nonce . "\n"
                    . $body_hash;

            $signature = hash_hmac('sha256', $message, $hmac_key);

            $extra_options = [
                'headers' => [
                    'Content-Type: application/json',
                    'X-IS2OR-Service: '   . $service_id,
                    'X-IS2OR-Timestamp: ' . $timestamp,
                    'X-IS2OR-Nonce: '     . $nonce,
                    'X-IS2OR-Signature: ' . $signature,
                    'X-IS2OR-Key-ID: '    . $key_id
                ],
                'ch_options' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_CONNECTTIMEOUT => 3,
                ]
            ];

            $api_response = Http::get(
                $sync_service_url . '/internal/imports/' . (int) $row['job_id'],
                '',
                $extra_options
            );

            if (is_string($api_response) && !empty($api_response)) {
                $api_data = json_decode($api_response, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($api_data) && isset($api_data['status'])) {

                    $new_status     = $api_data['status'];
                    $attempt_count  = isset($api_data['attempt_count']) ? (int) $api_data['attempt_count'] : $row['attempt_count'];
                    $success_count  = isset($api_data['success_count']) ? (int) $api_data['success_count'] : $row['success_count'];
                    $failure_count  = isset($api_data['failure_count']) ? (int) $api_data['failure_count'] : $row['failure_count'];
                    
                    $completed_at   = !empty($api_data['completed_at']) ? (int) $api_data['completed_at'] : null;

                    if ($completed_at !== null) {
                        db_query(
                            "UPDATE ?:is2or_channel_sync_history
                            SET status = ?s, attempt_count = ?i, success_count = ?i, failure_count = ?i, completed_at = ?i
                            WHERE id = ?i",
                            $new_status, $attempt_count, $success_count, $failure_count, $completed_at, $row['id']
                        );
                    } else {
                        db_query(
                            "UPDATE ?:is2or_channel_sync_history
                            SET status = ?s, attempt_count = ?i, success_count = ?i, failure_count = ?i, completed_at = NULL
                            WHERE id = ?i",
                            $new_status, $attempt_count, $success_count, $failure_count, $row['id']
                        );
                    }

                    $row['status']        = $new_status;
                    $row['attempt_count'] = $attempt_count;
                    $row['success_count'] = $success_count;
                    $row['failure_count'] = $failure_count;
                    $row['completed_at']  = $completed_at;
                }
            }
        }
    }
    unset($row);

    echo json_encode(['error' => false, 'data' => $histories]);
    exit;
}

// ====================================================================
// 6. MODE: Disconnect (FIXED FOR BYPASS SIGNATURE)
// ====================================================================
if ($mode == 'disconnect') {
    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'salla'",
        $company_id
    );

    if (empty($connection)) {
        fn_set_notification('E', __('error'), 'Connection data not found.');
        return [CONTROLLER_STATUS_REDIRECT, 'is2or_channel_integrations.manage'];
    }

    $connection_id = (int) $connection['connection_id'];
    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $payload = json_encode([
        'connection_id' => $connection_id,
        'company_id'    => (int) $company_id,
        'platform'  => 'salla'
    ]);

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    
    $body_hash = hash('sha256', $payload);
    $message = $service_id . "\n"
             . $timestamp . "\n"
             . $nonce . "\n"
             . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: ' . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: ' . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: ' . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $disconnect_url = $sync_service_url . '/internal/connections/' . $connection_id . '/disconnect';
    
    $response = Http::post($disconnect_url, $payload, $extra_options);
    $res_data = json_decode($response, true);

    if (is_string($response) && !empty($response) && json_last_error() === JSON_ERROR_NONE && is_array($res_data)) {
        if (!isset($res_data['error']) || $res_data['error'] == false) {
            
            db_query(
                "DELETE FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'salla'",
                $company_id
            );

            db_query(
                "DELETE FROM ?:is2or_channel_sync_history WHERE company_id = ?i AND platform = 'salla'",
                $company_id
            );

            fn_set_notification('N', __('notice'), 'Vendor channel disconnected successfully.');
        } else {
            $msg = isset($res_data['message']) ? $res_data['message'] : 'Unknown error from integration server.';
            fn_set_notification('E', __('error'), 'Failed to disconnect: ' . $msg);
        }
    } else {
        fn_set_notification('E', __('error'), 'No valid response from integration server.');
    }

    return [CONTROLLER_STATUS_REDIRECT, 'is2or_channel_integrations.manage'];
}

// ====================================================================
// 7. MODE: Request Authorize URL - Zid
// ====================================================================
if ($mode == 'request_authorize_url_zid') {
    header('Content-Type: application/json');

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');
    $return_url = fn_url("is2or_channel_integrations.callback_result", "A", "https");

    $payload = json_encode([
        'company_id' => (int) $company_id,
        'return_url' => $return_url
    ]);

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    $body_hash = hash('sha256', $payload);
    $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: '   . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: '     . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: '    . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $response = Http::post(
        $sync_service_url . '/internal/platforms/zid/authorize-url',
        $payload,
        $extra_options
    );

    if (!is_string($response) || trim($response) === '') {
        echo json_encode(['error' => true, 'message' => 'No response received from integration server.']);
        exit;
    }

    $res_data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($res_data)) {
        echo json_encode(['error' => true, 'message' => 'Invalid response from integration server.', 'debug' => substr(strip_tags($response), 0, 200)]);
        exit;
    }

    if (isset($res_data['authorization_url'])) {
        echo json_encode(['authorization_url' => $res_data['authorization_url']]);
    } else {
        echo json_encode(['error' => true, 'message' => isset($res_data['message']) ? $res_data['message'] : 'Failed to get authorization URL.']);
    }

    exit;
}

// ====================================================================
// 8. MODE: Trigger Import - Zid
// ====================================================================
if ($mode == 'trigger_import_zid') {
    header('Content-Type: application/json');

    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'zid'",
        $company_id
    );

    if (empty($connection) || $connection['status'] !== 'active') {
        echo json_encode(['error' => true, 'message' => 'Vendor is not connected to Zid channel.']);
        exit;
    }

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $payload = json_encode([
        'connection_id' => (int) $connection['connection_id'],
        'company_id'    => (int) $company_id,
        'import_type'   => 'products'
    ]);

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    $body_hash = hash('sha256', $payload);
    $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: '   . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: '     . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: '    . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $response = Http::post($sync_service_url . '/internal/imports', $payload, $extra_options);

    if (!is_string($response) || trim($response) === '') {
        echo json_encode(['error' => true, 'message' => 'No response received from integration server.']);
        exit;
    }

    $res_data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($res_data)) {
        echo json_encode(['error' => true, 'message' => 'Invalid response from integration server.', 'debug' => substr(strip_tags($response), 0, 200)]);
        exit;
    }

    if (isset($res_data['job_id'])) {
        db_query("INSERT INTO ?:is2or_channel_sync_history ?e", [
            'company_id'  => (int) $company_id,
            'platform'    => 'zid',
            'job_id'      => (string) $res_data['job_id'],
            'status'      => (string) $res_data['status'],
            'created_at'  => time()
        ]);

        // ----------------------------------------------------------------
        // Send the response to the user immediately, then continue running
        // the import-status polling + auto-categorization in the background
        // so the user does not have to wait for it.
        // ----------------------------------------------------------------
        echo json_encode([
            'error'   => false,
            'job_id'  => $res_data['job_id'],
            'message' => 'Data synchronization has been triggered successfully. Products will be imported gradually in the background.'
        ]);

        // // CS-Cart uses nested output buffering; loop to clear ALL levels
        // while (ob_get_level() > 0) {
        //     ob_end_clean();
        // }

        // header('Content-Type: application/json');
        // header('Content-Length: ' . strlen($response_body));
        // header('Connection: close');

        // echo $response_body;

        // if (function_exists('fastcgi_finish_request')) {
        //     fastcgi_finish_request();
        // } else {
        //     flush();
        // }

        // if (function_exists('set_time_limit')) {
        //     set_time_limit(0);
        // }
        // ignore_user_abort(true);

        // fn_is2or_poll_import_then_categorize(
        //     $res_data['job_id'],
        //     $connection['connection_id'],
        //     $company_id,
        //     $sync_service_url,
        //     $hmac_key
        // );
    } else {
        echo json_encode(['error' => true, 'message' => 'Failed to trigger import: ' . (isset($res_data['message']) ? $res_data['message'] : 'Unknown API error')]);
    }

    exit;
}

// ====================================================================
// 9. MODE: Sync History - Zid
// ====================================================================
if ($mode == 'sync_history_zid') {
    header('Content-Type: application/json');

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $histories = db_get_array(
        "SELECT id, job_id, status, attempt_count, success_count, failure_count, completed_at, created_at
         FROM ?:is2or_channel_sync_history
         WHERE company_id = ?i AND platform = 'zid'
         ORDER BY created_at DESC",
        $company_id
    );

    if (empty($histories)) {
        echo json_encode(['error' => false, 'data' => []]);
        exit;
    }

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    foreach ($histories as &$row) {
        if ($row['status'] === 'pending' || $row['status'] === 'leased') {

            $timestamp = (string) time();
            $nonce     = bin2hex(random_bytes(16));
            $body_hash = hash('sha256', '');
            $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
            $signature = hash_hmac('sha256', $message, $hmac_key);

            $extra_options = [
                'headers' => [
                    'Content-Type: application/json',
                    'X-IS2OR-Service: '   . $service_id,
                    'X-IS2OR-Timestamp: ' . $timestamp,
                    'X-IS2OR-Nonce: '     . $nonce,
                    'X-IS2OR-Signature: ' . $signature,
                    'X-IS2OR-Key-ID: '    . $key_id
                ],
                'ch_options' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_CONNECTTIMEOUT => 3,
                ]
            ];

            $api_response = Http::get(
                $sync_service_url . '/internal/imports/' . (int) $row['job_id'],
                '',
                $extra_options
            );

            if (is_string($api_response) && !empty($api_response)) {
                $api_data = json_decode($api_response, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($api_data) && isset($api_data['status'])) {
                    $new_status    = $api_data['status'];
                    $attempt_count = isset($api_data['attempt_count']) ? (int) $api_data['attempt_count'] : $row['attempt_count'];
                    $success_count = isset($api_data['success_count']) ? (int) $api_data['success_count'] : $row['success_count'];
                    $failure_count = isset($api_data['failure_count']) ? (int) $api_data['failure_count'] : $row['failure_count'];
                    $completed_at  = !empty($api_data['completed_at']) ? (int) $api_data['completed_at'] : null;

                    if ($completed_at !== null) {
                        db_query(
                            "UPDATE ?:is2or_channel_sync_history SET status = ?s, attempt_count = ?i, success_count = ?i, failure_count = ?i, completed_at = ?i WHERE id = ?i",
                            $new_status, $attempt_count, $success_count, $failure_count, $completed_at, $row['id']
                        );
                    } else {
                        db_query(
                            "UPDATE ?:is2or_channel_sync_history SET status = ?s, attempt_count = ?i, success_count = ?i, failure_count = ?i, completed_at = NULL WHERE id = ?i",
                            $new_status, $attempt_count, $success_count, $failure_count, $row['id']
                        );
                    }

                    $row['status']        = $new_status;
                    $row['attempt_count'] = $attempt_count;
                    $row['success_count'] = $success_count;
                    $row['failure_count'] = $failure_count;
                    $row['completed_at']  = $completed_at;
                }
            }
        }
    }
    unset($row);

    echo json_encode(['error' => false, 'data' => $histories]);
    exit;
}

// ====================================================================
// 10. MODE: Disconnect - Zid
// ====================================================================
if ($mode == 'disconnect_zid') {
    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'zid'",
        $company_id
    );

    if (empty($connection)) {
        fn_set_notification('E', __('error'), 'Connection data not found.');
        return [CONTROLLER_STATUS_REDIRECT, 'is2or_channel_integrations.manage'];
    }

    $connection_id    = (int) $connection['connection_id'];
    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $payload = json_encode([
        'connection_id' => $connection_id,
        'company_id'    => (int) $company_id,
        'platform'      => 'zid'
    ]);

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    $body_hash = hash('sha256', $payload);
    $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: '   . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: '     . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: '    . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $response = Http::post($sync_service_url . '/internal/connections/' . $connection_id . '/disconnect', $payload, $extra_options);
    $res_data = json_decode($response, true);

    if (is_string($response) && !empty($response) && json_last_error() === JSON_ERROR_NONE && is_array($res_data)) {
        if (!isset($res_data['error']) || $res_data['error'] == false) {
            db_query("DELETE FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'zid'", $company_id);
            db_query("DELETE FROM ?:is2or_channel_sync_history WHERE company_id = ?i AND platform = 'zid'", $company_id);
            fn_set_notification('N', __('notice'), 'Zid channel disconnected successfully.');
        } else {
            $msg = isset($res_data['message']) ? $res_data['message'] : 'Unknown error from integration server.';
            fn_set_notification('E', __('error'), 'Failed to disconnect: ' . $msg);
        }
    } else {
        db_query("DELETE FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'zid'", $company_id);
        db_query("DELETE FROM ?:is2or_channel_sync_history WHERE company_id = ?i AND platform = 'zid'", $company_id);
        fn_set_notification('W', __('warning'), 'Disconnected locally, but integration server did not respond. Please contact support if issues persist.');
    }

    return [CONTROLLER_STATUS_REDIRECT, 'is2or_channel_integrations.manage'];
}

// ====================================================================
// 11. MODE: Request Authorize URL - Noon
// ====================================================================
if ($mode == 'request_authorize_url_noon') {
    header('Content-Type: application/json');

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');
    $return_url = fn_url("is2or_channel_integrations.callback_result", "A", "https");

    $payload = json_encode([
        'company_id' => (int) $company_id,
        'return_url' => $return_url
    ]);

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    $body_hash = hash('sha256', $payload);
    $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: '   . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: '     . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: '    . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $response = Http::post(
        $sync_service_url . '/internal/platforms/noon/authorize-url',
        $payload,
        $extra_options
    );

    if (!is_string($response) || trim($response) === '') {
        echo json_encode(['error' => true, 'message' => 'No response received from integration server.']);
        exit;
    }

    $res_data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($res_data)) {
        echo json_encode(['error' => true, 'message' => 'Invalid response from integration server.', 'debug' => substr(strip_tags($response), 0, 200)]);
        exit;
    }

    if (isset($res_data['authorization_url'])) {
        echo json_encode(['authorization_url' => $res_data['authorization_url']]);
    } else {
        echo json_encode(['error' => true, 'message' => isset($res_data['message']) ? $res_data['message'] : 'Failed to get authorization URL.']);
    }

    exit;
}

// ====================================================================
// 12. MODE: Trigger Import - Noon
// ====================================================================
if ($mode == 'trigger_import_noon') {
    header('Content-Type: application/json');

    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'noon'",
        $company_id
    );

    if (empty($connection) || $connection['status'] !== 'active') {
        echo json_encode(['error' => true, 'message' => 'Vendor is not connected to Noon channel.']);
        exit;
    }

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $payload = json_encode([
        'connection_id' => (int) $connection['connection_id'],
        'company_id'    => (int) $company_id,
        'import_type'   => 'products'
    ]);

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    $body_hash = hash('sha256', $payload);
    $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: '   . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: '     . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: '    . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $response = Http::post($sync_service_url . '/internal/imports', $payload, $extra_options);

    if (!is_string($response) || trim($response) === '') {
        echo json_encode(['error' => true, 'message' => 'No response received from integration server.']);
        exit;
    }

    $res_data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($res_data)) {
        echo json_encode(['error' => true, 'message' => 'Invalid response from integration server.', 'debug' => substr(strip_tags($response), 0, 200)]);
        exit;
    }

    if (isset($res_data['job_id'])) {
        db_query("INSERT INTO ?:is2or_channel_sync_history ?e", [
            'company_id'  => (int) $company_id,
            'platform'    => 'noon',
            'job_id'      => (string) $res_data['job_id'],
            'status'      => (string) $res_data['status'],
            'created_at'  => time()
        ]);

        // ----------------------------------------------------------------
        // Send the response to the user immediately, then continue running
        // the import-status polling + auto-categorization in the background
        // so the user does not have to wait for it.
        // ----------------------------------------------------------------
        echo json_encode([
            'error'   => false,
            'job_id'  => $res_data['job_id'],
            'message' => 'Data synchronization has been triggered successfully. Products will be imported gradually in the background.'
        ]);
    } else {
        echo json_encode(['error' => true, 'message' => 'Failed to trigger import: ' . (isset($res_data['message']) ? $res_data['message'] : 'Unknown API error')]);
    }

    exit;
}

// ====================================================================
// 13. MODE: Sync History - Noon
// ====================================================================
if ($mode == 'sync_history_noon') {
    header('Content-Type: application/json');

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $histories = db_get_array(
        "SELECT id, job_id, status, attempt_count, success_count, failure_count, completed_at, created_at
         FROM ?:is2or_channel_sync_history
         WHERE company_id = ?i AND platform = 'noon'
         ORDER BY created_at DESC",
        $company_id
    );

    if (empty($histories)) {
        echo json_encode(['error' => false, 'data' => []]);
        exit;
    }

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    foreach ($histories as &$row) {
        if ($row['status'] === 'pending' || $row['status'] === 'leased') {

            $timestamp = (string) time();
            $nonce     = bin2hex(random_bytes(16));
            $body_hash = hash('sha256', '');
            $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
            $signature = hash_hmac('sha256', $message, $hmac_key);

            $extra_options = [
                'headers' => [
                    'Content-Type: application/json',
                    'X-IS2OR-Service: '   . $service_id,
                    'X-IS2OR-Timestamp: ' . $timestamp,
                    'X-IS2OR-Nonce: '     . $nonce,
                    'X-IS2OR-Signature: ' . $signature,
                    'X-IS2OR-Key-ID: '    . $key_id
                ],
                'ch_options' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_CONNECTTIMEOUT => 3,
                ]
            ];

            $api_response = Http::get(
                $sync_service_url . '/internal/imports/' . (int) $row['job_id'],
                '',
                $extra_options
            );

            if (is_string($api_response) && !empty($api_response)) {
                $api_data = json_decode($api_response, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($api_data) && isset($api_data['status'])) {
                    $new_status    = $api_data['status'];
                    $attempt_count = isset($api_data['attempt_count']) ? (int) $api_data['attempt_count'] : $row['attempt_count'];
                    $success_count = isset($api_data['success_count']) ? (int) $api_data['success_count'] : $row['success_count'];
                    $failure_count = isset($api_data['failure_count']) ? (int) $api_data['failure_count'] : $row['failure_count'];
                    $completed_at  = !empty($api_data['completed_at']) ? (int) $api_data['completed_at'] : null;

                    if ($completed_at !== null) {
                        db_query(
                            "UPDATE ?:is2or_channel_sync_history SET status = ?s, attempt_count = ?i, success_count = ?i, failure_count = ?i, completed_at = ?i WHERE id = ?i",
                            $new_status, $attempt_count, $success_count, $failure_count, $completed_at, $row['id']
                        );
                    } else {
                        db_query(
                            "UPDATE ?:is2or_channel_sync_history SET status = ?s, attempt_count = ?i, success_count = ?i, failure_count = ?i, completed_at = NULL WHERE id = ?i",
                            $new_status, $attempt_count, $success_count, $failure_count, $row['id']
                        );
                    }

                    $row['status']        = $new_status;
                    $row['attempt_count'] = $attempt_count;
                    $row['success_count'] = $success_count;
                    $row['failure_count'] = $failure_count;
                    $row['completed_at']  = $completed_at;
                }
            }
        }
    }
    unset($row);

    echo json_encode(['error' => false, 'data' => $histories]);
    exit;
}

// ====================================================================
// 14. MODE: Disconnect - Noon
// ====================================================================
if ($mode == 'disconnect_noon') {
    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'noon'",
        $company_id
    );

    if (empty($connection)) {
        fn_set_notification('E', __('error'), 'Connection data not found.');
        return [CONTROLLER_STATUS_REDIRECT, 'is2or_channel_integrations.manage'];
    }

    $connection_id    = (int) $connection['connection_id'];
    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    $payload = json_encode([
        'connection_id' => $connection_id,
        'company_id'    => (int) $company_id,
        'platform'      => 'noon'
    ]);

    $service_id = 'local-test';
    $hmac_key   = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $key_id     = 'key-v1';

    $timestamp = (string) time();
    $nonce     = bin2hex(random_bytes(16));
    $body_hash = hash('sha256', $payload);
    $message   = $service_id . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
    $signature = hash_hmac('sha256', $message, $hmac_key);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json',
            'X-IS2OR-Service: '   . $service_id,
            'X-IS2OR-Timestamp: ' . $timestamp,
            'X-IS2OR-Nonce: '     . $nonce,
            'X-IS2OR-Signature: ' . $signature,
            'X-IS2OR-Key-ID: '    . $key_id
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]
    ];

    $response = Http::post($sync_service_url . '/internal/connections/' . $connection_id . '/disconnect', $payload, $extra_options);
    $res_data = json_decode($response, true);

    if (is_string($response) && !empty($response) && json_last_error() === JSON_ERROR_NONE && is_array($res_data)) {
        if (!isset($res_data['error']) || $res_data['error'] == false) {
            db_query("DELETE FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'noon'", $company_id);
            db_query("DELETE FROM ?:is2or_channel_sync_history WHERE company_id = ?i AND platform = 'noon'", $company_id);
            fn_set_notification('N', __('notice'), 'Noon channel disconnected successfully.');
        } else {
            $msg = isset($res_data['message']) ? $res_data['message'] : 'Unknown error from integration server.';
            fn_set_notification('E', __('error'), 'Failed to disconnect: ' . $msg);
        }
    } else {
        db_query("DELETE FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'noon'", $company_id);
        db_query("DELETE FROM ?:is2or_channel_sync_history WHERE company_id = ?i AND platform = 'noon'", $company_id);
        fn_set_notification('W', __('warning'), 'Disconnected locally, but integration server did not respond. Please contact support if issues persist.');
    }

    return [CONTROLLER_STATUS_REDIRECT, 'is2or_channel_integrations.manage'];
}

// ====================================================================
// 15. MODE: Generate Category (AI Bulk Categorize) - Noon
// ====================================================================
if ($mode == 'generate_category_noon') {
    header('Content-Type: application/json');

    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'noon'",
        $company_id
    );

    $debug_log[] = 'connection row: ' . var_export($connection, true);

    if (empty($connection) || $connection['status'] !== 'active') {
        $debug_log[] = 'ABORT: connection empty or not active. status=' . ($connection['status'] ?? 'NULL');
        echo json_encode([
            'error'     => true,
            'message'   => 'Vendor is not connected to Noon.',
            'debug_log' => $debug_log
        ]);
        exit;
    }

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');
    $hmac_key = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $debug_log[] = 'sync_service_url=' . var_export($sync_service_url, true) . ' hmac_key_set=' . (empty($hmac_key) ? 'NO' : 'YES');

    $result_error = false;
    $result_message = 'Generate AI categories completed.';

    try {
        $debug_log[] = 'calling fn_is2or_run_bulk_categorization connection_id=' . $connection['connection_id'] . ' company_id=' . $company_id;

        fn_is2or_run_bulk_categorization(
            $connection['connection_id'],
            $company_id,
            $debug_log
        );

        $debug_log[] = 'fn_is2or_run_bulk_categorization FINISHED OK';
    } catch (\Throwable $e) {
        $result_error = true;
        $result_message = 'Error: ' . $e->getMessage();
        $debug_log[] = 'EXCEPTION: ' . $e->getMessage() . ' | file=' . $e->getFile() . ' line=' . $e->getLine();
    }

    echo json_encode([
        'error'     => $result_error,
        'message'   => $result_message,
        'debug_log' => $debug_log
    ]);
    exit;
}

// ====================================================================
// MODE: Generate Category (AI Bulk Categorize)
// ====================================================================
if ($mode == 'generate_category') {
    header('Content-Type: application/json');

    $debug_log = [];
    $debug_log[] = 'START company_id=' . $company_id;

    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'salla'",
        $company_id
    );

    $debug_log[] = 'connection row: ' . var_export($connection, true);

    if (empty($connection) || $connection['status'] !== 'active') {
        $debug_log[] = 'ABORT: connection empty or not active. status=' . ($connection['status'] ?? 'NULL');
        echo json_encode([
            'error'     => true,
            'message'   => 'Vendor is not connected to Salla.',
            'debug_log' => $debug_log
        ]);
        exit;
    }

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');
    $hmac_key = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $debug_log[] = 'sync_service_url=' . var_export($sync_service_url, true) . ' hmac_key_set=' . (empty($hmac_key) ? 'NO' : 'YES');

    $result_error = false;
    $result_message = 'Generate AI categories completed.';

    try {
        $debug_log[] = 'calling fn_is2or_run_bulk_categorization connection_id=' . $connection['connection_id'] . ' company_id=' . $company_id;

        fn_is2or_run_bulk_categorization(
            $connection['connection_id'],
            $company_id,
            $debug_log
        );

        $debug_log[] = 'fn_is2or_run_bulk_categorization FINISHED OK';
    } catch (\Throwable $e) {
        $result_error = true;
        $result_message = 'Error: ' . $e->getMessage();
        $debug_log[] = 'EXCEPTION: ' . $e->getMessage() . ' | file=' . $e->getFile() . ' line=' . $e->getLine();
    }

    echo json_encode([
        'error'     => $result_error,
        'message'   => $result_message,
        'debug_log' => $debug_log
    ]);
    exit;
}

// ====================================================================
// MODE: Generate Category (AI Bulk Categorize)
// ====================================================================
if ($mode == 'generate_category_zid') {
    header('Content-Type: application/json');

    $connection = db_get_row(
        "SELECT connection_id, status FROM ?:is2or_channel_connections WHERE company_id = ?i AND platform = 'zid'",
        $company_id
    );

    $debug_log[] = 'connection row: ' . var_export($connection, true);

    if (empty($connection) || $connection['status'] !== 'active') {
        $debug_log[] = 'ABORT: connection empty or not active. status=' . ($connection['status'] ?? 'NULL');
        echo json_encode([
            'error'     => true,
            'message'   => 'Vendor is not connected to Salla.',
            'debug_log' => $debug_log
        ]);
        exit;
    }

    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');
    $hmac_key = Registry::get('addons.is2or_channel_integrations.internal_hmac_secret');
    $debug_log[] = 'sync_service_url=' . var_export($sync_service_url, true) . ' hmac_key_set=' . (empty($hmac_key) ? 'NO' : 'YES');

    $result_error = false;
    $result_message = 'Generate AI categories completed.';

    try {
        $debug_log[] = 'calling fn_is2or_run_bulk_categorization connection_id=' . $connection['connection_id'] . ' company_id=' . $company_id;

        fn_is2or_run_bulk_categorization(
            $connection['connection_id'],
            $company_id,
            $debug_log
        );

        $debug_log[] = 'fn_is2or_run_bulk_categorization FINISHED OK';
    } catch (\Throwable $e) {
        $result_error = true;
        $result_message = 'Error: ' . $e->getMessage();
        $debug_log[] = 'EXCEPTION: ' . $e->getMessage() . ' | file=' . $e->getFile() . ' line=' . $e->getLine();
    }

    echo json_encode([
        'error'     => $result_error,
        'message'   => $result_message,
        'debug_log' => $debug_log
    ]);
    exit;
}