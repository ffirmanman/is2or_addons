<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Http;

/**
 * Helper function to get channel connection status
 * for a specific vendor/company
 */
function fn_is2or_get_channel_connection_status($company_id, $platform = 'salla')
{
    $status = db_get_field(
        "SELECT status
         FROM ?:is2or_channel_connections
         WHERE company_id = ?i
           AND platform = ?s",
        $company_id,
        $platform
    );

    return !empty($status) ? $status : 'disconnected';
}

/**
 * Helper function to get ALL active channel connections for a company,
 * across every supported platform (salla, zid, noon, ...).
 *
 * Adding a new channel does NOT require touching this function or any
 * of the hooks below — as long as the connection row exists in
 * ?:is2or_channel_connections with status = 'active', it will be picked up.
 *
 * @param int $company_id
 * @return array List of rows: ['connection_id' => int, 'platform' => string, 'status' => string]
 */
if (!function_exists('fn_is2or_get_active_connections')) {
    function fn_is2or_get_active_connections($company_id)
    {
        $connections = db_get_array(
            "SELECT connection_id, platform, status
             FROM ?:is2or_channel_connections
             WHERE company_id = ?i
               AND status = 'active'",
            (int) $company_id
        );

        return !empty($connections) ? $connections : [];
    }
}

/**
 * Helper function to insert an outbox row and immediately push the
 * product mutation to a given connection. Shared by the update/order hooks
 * so every channel goes through the exact same outbox + push logic.
 *
 * @param int    $product_id
 * @param int    $company_id
 * @param int    $connection_id
 * @param string $target_action 'create' | 'update' | 'delete'
 * @param string $initial_status 'processing' | 'pending'
 * @return void
 */
if (!function_exists('fn_is2or_enqueue_product_outbox_and_push')) {
    function fn_is2or_enqueue_product_outbox_and_push($product_id, $company_id, $connection_id, $target_action, $initial_status = 'processing')
    {
        $product_id    = (int) $product_id;
        $company_id    = (int) $company_id;
        $connection_id = (int) $connection_id;

        if ($initial_status === 'processing') {
            $is_processing = db_get_field(
                "SELECT outbox_id
                 FROM ?:is2or_channel_product_outbox
                 WHERE product_id = ?i
                   AND company_id = ?i
                   AND connection_id = ?i
                   AND status = 'processing'
                 LIMIT 1",
                $product_id,
                $company_id,
                $connection_id
            );

            if (!empty($is_processing)) {
                return;
            }
        }

        $outbox_data = [
            'product_id'      => $product_id,
            'company_id'      => $company_id,
            'connection_id'   => $connection_id,
            'target_action'   => $target_action,
            'event_source'    => 'is2or',
            'change_hash'     => hash('sha256', $product_id . '_' . $company_id . '_' . $connection_id . '_' . $target_action . '_' . time()),
            'idempotency_key' => hash('sha256', $product_id . '_' . $company_id . '_' . $connection_id . '_' . $target_action . '_' . uniqid('', true)),
            'status'          => $initial_status,
            'attempt_count'   => 0,
            'next_attempt_at' => 0,
            'created_at'      => time(),
            'updated_at'      => time()
        ];

        $outbox_id = db_query(
            "INSERT INTO ?:is2or_channel_product_outbox ?e",
            $outbox_data
        );

        if (empty($outbox_id)) {
            return;
        }

        $is_pushed = fn_is2or_push_product_to_link(
            $connection_id,
            $company_id,
            $product_id,
            $target_action
        );

        db_query(
            "UPDATE ?:is2or_channel_product_outbox
             SET status = ?s, updated_at = ?i
             WHERE outbox_id = ?i",
            $is_pushed ? 'success' : 'failed',
            time(),
            $outbox_id
        );
    }
}

/**
 * Hook after a product is created or updated (Admin / Vendor Panel)
 *
 * Pushes the product to EVERY active channel connection the vendor has
 * (Salla, Zid, Noon, ...), not just the most recently mapped one.
 */
function fn_is2or_channel_integrations_update_product_post($product_data, $product_id, $lang_code, $action)
{
    $company_id = !empty($product_data['company_id'])
        ? $product_data['company_id']
        : db_get_field(
            "SELECT company_id
             FROM ?:products
             WHERE product_id = ?i",
            $product_id
        );

    if (empty($company_id)) {
        return;
    }

    $connections = fn_is2or_get_active_connections($company_id);

    if (empty($connections)) {
        return;
    }

    $target_action = ($action == 'add') ? 'create' : 'update';

    foreach ($connections as $connection) {
        fn_is2or_enqueue_product_outbox_and_push(
            $product_id,
            $company_id,
            $connection['connection_id'],
            $target_action,
            'processing'
        );
    }
}


/**
 * Hook after a product is deleted
 *
 * Enqueues a background delete job for EVERY active channel connection
 * the vendor has (Salla, Zid, Noon, ...).
 */
function fn_is2or_channel_integrations_delete_product_post($product_id, $product_deleted)
{
    if (!$product_deleted) {
        return;
    }

    $company_id = db_get_field(
        "SELECT company_id 
         FROM ?:is2or_channel_product_outbox 
         WHERE product_id = ?i 
         ORDER BY outbox_id DESC LIMIT 1",
        $product_id
    );

    if (empty($company_id)) {
        $company_id = fn_get_runtime_company_id();
    }

    if (empty($company_id)) {
        return;
    }

    $connections = fn_is2or_get_active_connections($company_id);

    if (empty($connections)) {
        return;
    }

    $target_action = 'delete';

    foreach ($connections as $connection) {
        $connection_id = (int) $connection['connection_id'];

        $outbox_data = [
            'product_id'      => (int) $product_id,
            'company_id'      => (int) $company_id,
            'connection_id'   => $connection_id,
            'target_action'   => $target_action,
            'event_source'    => 'is2or',
            'change_hash'     => hash('sha256', $product_id . '_' . $company_id . '_' . $connection_id . '_' . $target_action . '_' . time()),
            'idempotency_key' => hash('sha256', $product_id . '_' . $company_id . '_' . $connection_id . '_' . $target_action . '_' . uniqid('', true)),
            'status'          => 'pending',
            'attempt_count'   => 0,
            'next_attempt_at' => 0,
            'created_at'      => time(),
            'updated_at'      => time()
        ];

        $outbox_id = db_query(
            "INSERT INTO ?:is2or_channel_product_outbox ?e",
            $outbox_data
        );

        if (empty($outbox_id)) {
            continue;
        }

        fn_is2or_enqueue_background_delete((int) $outbox_id, $connection_id, $company_id, $product_id);
    }
}

/**
 * Collect data delete
 */
if (!function_exists('fn_is2or_enqueue_background_delete')) {
    function fn_is2or_enqueue_background_delete($outbox_id, $connection_id, $company_id, $product_id)
    {
        $queue = Registry::get('runtime.is2or_delete_queue');
        if (!is_array($queue)) {
            $queue = [];
        }

        $group_key = $connection_id . '_' . $company_id;

        if (!isset($queue[$group_key])) {
            $queue[$group_key] = [
                'connection_id' => (int) $connection_id,
                'company_id'    => (int) $company_id,
                'items'         => [], // ['outbox_id' => ..., 'product_id' => ...]
            ];
        }

        $queue[$group_key]['items'][] = [
            'outbox_id'  => (int) $outbox_id,
            'product_id' => (int) $product_id,
        ];

        Registry::set('runtime.is2or_delete_queue', $queue);

        if (!Registry::get('runtime.is2or_delete_notified')) {
            Registry::set('runtime.is2or_delete_notified', true);
            fn_set_notification(
                'N',
                __('notice'),
                __('is2or_channel_integrations.delete_product')
            );
        }

        if (!Registry::get('runtime.is2or_delete_shutdown_registered')) {
            Registry::set('runtime.is2or_delete_shutdown_registered', true);
            register_shutdown_function('fn_is2or_process_background_delete_queue');
        }
    }
}

/**
 * Run after response sent to browser
 *
 * Each queue group is keyed by connection_id + company_id, so Salla, Zid,
 * Noon (and any future channel) are always processed as separate bulk-delete
 * calls, one per connection, even when several channels are active for the
 * same vendor.
 */
if (!function_exists('fn_is2or_process_background_delete_queue')) {
    function fn_is2or_process_background_delete_queue()
    {
        $queue = Registry::get('runtime.is2or_delete_queue');

        if (empty($queue) || !is_array($queue)) {
            return;
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
        ignore_user_abort(true);

        foreach ($queue as $group) {
            $connection_id = $group['connection_id'];
            $company_id    = $group['company_id'];
            $items         = $group['items'];

            $outbox_ids = array_column($items, 'outbox_id');
            $product_ids = array_column($items, 'product_id');

            if (empty($product_ids)) {
                continue;
            }

            db_query(
                "UPDATE ?:is2or_channel_product_outbox SET status = 'processing', updated_at = ?i WHERE outbox_id IN (?n)",
                time(),
                $outbox_ids
            );

            $result = fn_is2or_push_bulk_delete_to_link($connection_id, $company_id, $product_ids);

            $is_success = !empty($result)
                && isset($result['status']) && $result['status'] === 'pending'
                && isset($result['queued_count']) && (int) $result['queued_count'] === count($product_ids);

            db_query(
                "UPDATE ?:is2or_channel_product_outbox SET status = ?s, updated_at = ?i WHERE outbox_id IN (?n)",
                $is_success ? 'success' : 'failed',
                time(),
                $outbox_ids
            );
        }
    }
}

/**
 * Internal function to push a BATCH of product delete mutations in one HTTP call.
 * Platform-agnostic: the sync service resolves the target channel from
 * connection_id, so this works unchanged for Salla, Zid, Noon, etc.
 */
if (!function_exists('fn_is2or_push_bulk_delete_to_link')) {
    function fn_is2or_push_bulk_delete_to_link($connection_id, $company_id, array $product_ids)
    {
        $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

        if (empty($sync_service_url) || empty($product_ids)) {
            return false;
        }

        $payload = json_encode([
            'connection_id' => (int) $connection_id,
            'company_id'    => (int) $company_id,
            'product_ids'   => array_values(array_map('intval', $product_ids)),
            'action'        => 'delete'
        ]);

        $extra_options = [
            'headers' => [
                'Content-Type: application/json'
            ],
            'ch_options' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 15
            ]
        ];

        $api_url = rtrim($sync_service_url, '/') . '/internal/product-syncs/bulk';

        $response = Http::post($api_url, $payload, $extra_options);

        if (empty($response)) {
            return false;
        }

        $res_data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($res_data)) {
            return false;
        }

        return $res_data;
    }
}

/**
 * Internal function to push product mutation data.
 * Platform-agnostic: the sync service resolves the target channel from
 * connection_id, so this works unchanged for Salla, Zid, Noon, etc.
 */
function fn_is2or_push_product_to_link($connection_id, $company_id, $product_id, $action)
{
    $sync_service_url = Registry::get('addons.is2or_channel_integrations.sync_service_base_url');

    if (empty($sync_service_url)) {
        return false;
    }

    $payload = json_encode([
        'connection_id' => (int) $connection_id,
        'company_id'    => (int) $company_id,
        'product_id'    => (int) $product_id,
        'action'        => $action // create, update, delete
    ]);

    $extra_options = [
        'headers' => [
            'Content-Type: application/json'
        ],
        'ch_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10
        ]
    ];

    $api_url = rtrim($sync_service_url, '/') . '/internal/product-syncs';
    
    $response = Http::post($api_url, $payload, $extra_options);

    if (!empty($response)) {
        $res_data = json_decode($response, true);
        
        if (isset($res_data['status']) && $res_data['status'] === 'pending') {
            return true;
        }
    }

    return false;
}

/**
 * Hook to handle stock changes caused by Order transactions (Deduct & Restore)
 *
 * Pushes the stock update to EVERY active channel connection the vendor
 * has (Salla, Zid, Noon, ...) for each affected product.
 */
function fn_is2or_channel_integrations_change_order_status(
    $status_to,
    $status_from,
    $order_info,
    $force_notification,
    $order_statuses,
    $place_order
) {
    if (empty($order_info['products'])) {
        return;
    }

    $inventory_consuming_statuses = ['P', 'C', 'G', 'A', 'E']; // Paid, Complete, Delivered, Packing, Shipping
    $inventory_restoring_statuses = ['I', 'F', 'D']; // Canceled, Failed, Declined

    $was_consumed = in_array($status_from, $inventory_consuming_statuses);
    $is_consumed  = in_array($status_to, $inventory_consuming_statuses);
    $is_restored  = in_array($status_to, $inventory_restoring_statuses);

    $should_trigger_update = false;

    if (!$was_consumed && $is_consumed) {
        $should_trigger_update = true;
    }
    elseif (($was_consumed || $status_from == 'O') && $is_restored) {
        $should_trigger_update = true;
    }

    if (!$should_trigger_update) {
        return;
    }

    // Cache active connections per company_id so we don't re-query the DB
    // for every product line that belongs to the same vendor.
    $connections_by_company = [];

    foreach ($order_info['products'] as $item) {

        $product_id = (int) $item['product_id'];

        $company_id = !empty($item['company_id'])
            ? (int) $item['company_id']
            : (int) db_get_field("SELECT company_id FROM ?:products WHERE product_id = ?i", $product_id);

        if (empty($company_id)) {
            continue;
        }

        if (!isset($connections_by_company[$company_id])) {
            $connections_by_company[$company_id] = fn_is2or_get_active_connections($company_id);
        }

        $connections = $connections_by_company[$company_id];

        if (empty($connections)) {
            continue;
        }

        foreach ($connections as $connection) {
            fn_is2or_enqueue_product_outbox_and_push(
                $product_id,
                $company_id,
                $connection['connection_id'],
                'update',
                'processing'
            );
        }
    }
}
