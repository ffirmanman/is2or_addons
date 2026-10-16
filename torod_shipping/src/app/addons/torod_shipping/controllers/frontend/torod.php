<?php

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Torod webhook endpoint
 *
 * GET  — health check (Torod dashboard activation test)
 * POST — webhook payload (Torod sends order/tracking updates)
 *
 * Always returns 200 to keep endpoint "active" in Torod dashboard.
 * If the payload is a Torod test ping (placeholder values), log + ack only.
 * If the payload is real, process shipment update if order_id found, log errors silently.
 * Soft validation: secret / HMAC mismatches are logged but never reject with 4xx.
 */

// GET — health check for Torod dashboard activation
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(array(
        'status' => 'success',
        'message' => 'Torod webhook endpoint is active',
    ));
    exit;
}

// Non-POST — silently ack, don't break dashboard handshake
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(array(
        'status' => 'success',
        'message' => 'acknowledged',
    ));
    exit;
}

/** ─────────────────────────── POST ─────────────────────────── */
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);
if (empty($data) || !is_array($data)) {
    fn_log_event('general', 'runtime', array(
        'message' => 'Torod webhook: empty or invalid payload'
    ));
    http_response_code(200);
    echo json_encode(array('status' => 'success', 'message' => 'acknowledged'));
    exit;
}

// Log the entire request for troubleshooting
$all_headers = function_exists('getallheaders') ? getallheaders() : array();
$debug_message = "Torod webhook received\n"
    . "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n"
    . "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n"
    . "HTTP_AUTHORIZATION: " . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT_SET') . "\n"
    . "HTTP_X_HMAC_SHA256: " . ($_SERVER['HTTP_X_HMAC_SHA256'] ?? 'NOT_SET') . "\n"
    . "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT_SET') . "\n"
    . "All Headers: " . json_encode($all_headers) . "\n"
    . "Raw Payload: " . $payload . "\n"
    . "Parsed Data: " . json_encode($data);
fn_log_event('general', 'runtime', array('message' => $debug_message));

// Detect Torod test ping (placeholder values wrapped in {})
$is_test_ping = false;
if (isset($data['order_id']) && strpos($data['order_id'], '{') === 0) {
    $is_test_ping = true;
}
if (!$is_test_ping && isset($data['tracking_id']) && strpos($data['tracking_id'], '{') === 0) {
    $is_test_ping = true;
}

if ($is_test_ping) {
    fn_log_event('general', 'runtime', array(
        'message' => 'Torod webhook: test ping acknowledged'
    ));
    http_response_code(200);
    echo json_encode(array('status' => 'success', 'message' => 'test ping acknowledged'));
    exit;
}

// Check mode
if (empty($mode) || $mode !== 'webhook') {
    fn_log_event('general', 'runtime', array(
        'message' => 'Torod webhook: invalid mode'
    ));
    http_response_code(200);
    echo json_encode(array('status' => 'success', 'message' => 'acknowledged'));
    exit;
}

// Validate required fields
if (empty($data['order_id']) || empty($data['shipment_status'])) {
    fn_log_event('general', 'runtime', array(
        'message' => 'Torod webhook: missing required fields'
    ));
    http_response_code(200);
    echo json_encode(array('status' => 'success', 'message' => 'acknowledged'));
    exit;
}

// Soft auth check — read from Authorization header first (Torod documented),
// fall back to body field. Log warning on mismatch, never reject.
$header_secret = '';
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $header_secret = trim($_SERVER['HTTP_AUTHORIZATION']);
} elseif (!empty($all_headers['Authorization'])) {
    $header_secret = trim($all_headers['Authorization']);
}

$body_secret = trim($data['client_secret_key'] ?? '');
$received_secret = !empty($header_secret) ? $header_secret : $body_secret;
$secret_valid = !empty($received_secret) && $received_secret === TOROD_CLIENT_SECRET;

if (!$secret_valid) {
    fn_log_event('general', 'runtime', array(
        'message' => 'Torod webhook: client_secret_key mismatch'
    ));
}

// HMAC verification — log warning if mismatch, never reject
$hmac_header = $_SERVER['HTTP_X_HMAC_SHA256'] ?? '';
$hmac_valid = true;
if (!empty($hmac_header) && !empty(TOROD_CLIENT_SECRET)) {
    $expected_hmac = base64_encode(hash_hmac('sha256', $payload, TOROD_CLIENT_SECRET, true));
    if (!hash_equals($expected_hmac, $hmac_header)) {
        fn_log_event('general', 'runtime', array(
            'message' => 'Torod webhook: HMAC signature mismatch'
        ));
        $hmac_valid = false;
    }
}

// Find Torod shipment record
$torod_shipment = db_get_row(
    'SELECT * FROM ?:torod_shipments WHERE order_id = ?s ORDER BY id DESC LIMIT 1',
    $data['order_id']
);

if (empty($torod_shipment)) {
    fn_log_event('general', 'runtime', array(
        'message' => 'Torod webhook: shipment not found for order_id ' . $data['order_id']
    ));
    http_response_code(200);
    echo json_encode(array(
        'status' => 'success',
        'message' => 'shipment not in our records',
    ));
    exit;
}

$shipment_id = $torod_shipment['shipment_id'];
$order_id = $torod_shipment['order_id'];

// Update torod_shipments table
$update_data = array(
    'torod_tracking_id' => $data['tracking_id'] ?? $torod_shipment['torod_tracking_id'],
    'courier_name' => $data['courier_name'] ?? $torod_shipment['courier_name'],
    'delivery_tracking_url' => $data['torod_shipment_tracking_url'] ?? $torod_shipment['delivery_tracking_url'],
    'awb_url' => $data['aws_label'] ?? $torod_shipment['awb_url'],
    'status' => $data['shipment_status'],
    'updated_at' => TIME,
);
db_query('UPDATE ?:torod_shipments SET ?u WHERE id = ?i', $update_data, $torod_shipment['id']);

// Update ?:shipments tracking number
if (!empty($data['tracking_id'])) {
    db_query('UPDATE ?:shipments SET tracking_number = ?s WHERE shipment_id = ?i',
        $data['tracking_id'], $shipment_id);
}

// Status mapping
$shipment_status = $data['shipment_status'];

$cs_status_map = array(
    'READY_FOR_PICKUP' => 'P',
    'PENDING_PICKUP' => 'P',
    'PICKED_UP' => 'S',
    'IN_TRANSIT' => 'S',
    'OUT_FOR_DELIVERY' => 'S',
    'PARTIAL_DELIVERY' => 'S',
    'DELIVERED' => 'D',
    'FAILED_TO_DELIVER' => 'A',
    'FAILED_TO_ATTEMPT' => 'A',
    'DAMAGE' => 'A',
    'RESCHEDULED' => 'I',
);

$destructive_statuses = array('CANCELLED', 'RTO_IN_PROGRESS', 'RTO', 'LOST');

if (in_array($shipment_status, $destructive_statuses)) {
    db_query('DELETE FROM ?:shipments WHERE shipment_id = ?i', $shipment_id);
    db_query('DELETE FROM ?:shipment_items WHERE shipment_id = ?i', $shipment_id);
} elseif (isset($cs_status_map[$shipment_status])) {
    fn_tools_update_status(array(
        'table' => 'shipments',
        'status' => $cs_status_map[$shipment_status],
        'id_name' => 'shipment_id',
        'id' => $shipment_id,
        'show_error_notice' => false,
    ));
}

// Order status mapping from addon settings
$order_status_key = 'webhook_' . strtolower($shipment_status);
$mapped_status = Registry::get('addons.torod_shipping.' . $order_status_key);
if (!empty($mapped_status)) {
    fn_change_order_status($order_id, $mapped_status);
}

header('Content-Type: application/json; charset=utf-8');
http_response_code(200);
echo json_encode(array(
    'status' => 'success',
    'message' => 'Updated status to: ' . $shipment_status,
));
exit;
