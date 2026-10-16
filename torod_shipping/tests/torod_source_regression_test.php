<?php

declare(strict_types=1);

$addon_root = dirname(__DIR__);

$func_path        = $addon_root . '/src/app/addons/torod_shipping/func.php';
$torod_path       = $addon_root . '/src/app/addons/torod_shipping/Tygh/Shippings/Services/Torod.php';
$webhook_path     = $addon_root . '/src/app/addons/torod_shipping/controllers/frontend/torod.php';
$addon_xml_path   = $addon_root . '/src/app/addons/torod_shipping/addon.xml';

$func_source    = file_get_contents($func_path);
$torod_source   = file_get_contents($torod_path);
$webhook_source = file_get_contents($webhook_path);
$addon_xml      = file_get_contents($addon_xml_path);

if ($func_source === false || $torod_source === false || $webhook_source === false || $addon_xml === false) {
    fwrite(STDERR, "Failed to load Torod addon source files.\n");
    exit(1);
}

$errors = [];

// ── Unified API client ──

if (strpos($func_source, "function fn_torod_api_post") === false) {
    $errors[] = 'Missing fn_torod_api_post function.';
}

if (strpos($func_source, "\$mode = 'form'") === false && strpos($func_source, "\$mode=\"form\"") === false) {
    $errors[] = 'fn_torod_api_post missing $mode=form default parameter.';
}

if (strpos($func_source, "application/x-www-form-urlencoded") === false) {
    $errors[] = 'fn_torod_api_post does not send Content-Type: application/x-www-form-urlencoded.';
}

// ── Live-path JSON regressions ──

if (strpos($func_source, "Http::post(" . '$api_url' . " . '/order/create', json_encode(" . '$data' . ")") !== false) {
    $errors[] = 'order/create still posts JSON directly instead of using fn_torod_api_post(..., form).';
}

if (strpos($func_source, "Http::post(" . '$api_url' . " . '/order/ship/process', json_encode(" . '$ship_data' . ")") !== false) {
    $errors[] = 'order/ship/process still posts JSON directly instead of using fn_torod_api_post(..., form).';
}

if (strpos($func_source, "Http::post(" . '$api_url' . " . '/order/cancel', json_encode(" . '$data' . ")") !== false) {
    $errors[] = 'order/cancel still posts JSON directly instead of using fn_torod_api_post(..., form).';
}

// ── Token deduplication ──

if (strpos($torod_source, "fn_torod_get_access_token()") === false) {
    $errors[] = 'Torod.php _accessToken() does not delegate to fn_torod_get_access_token().';
}

if (strpos($torod_source, "client_id") !== false || strpos($torod_source, "client_secret") !== false || strpos($torod_source, "bearer_token") !== false) {
    $errors[] = 'Torod.php still contains token-fetch internals — should be fully delegated.';
}

// ── order/create payload ──

if (strpos($func_source, "'city_id'") === false) {
    $errors[] = 'order/create payload missing city_id field.';
}

if (strpos($func_source, "'address'") === false) {
    $errors[] = 'order/create payload missing address field.';
}

if (strpos($func_source, "empty(\$data['city_id'])") === false && strpos($func_source, 'empty($data[\'city_id\'])') === false) {
    $errors[] = 'Missing city_id empty guard after hooks.';
}

// ── Webhook auth ──

if (strpos($webhook_source, "HTTP_AUTHORIZATION") === false) {
    $errors[] = 'Webhook does not read Authorization header.';
}

if (strpos($webhook_source, "client_secret_key") === false) {
    $errors[] = 'Webhook lost body fallback for client_secret_key.';
}

if (strpos($webhook_source, "X_HMAC_SHA256") === false && strpos($webhook_source, 'HTTP_X_HMAC_SHA256') === false) {
    $errors[] = 'Webhook lost HMAC verification.';
}

// ── Warehouse creation success check ──
// Torod API returns {"status": true} (boolean), not "success" (string)
// Using === 'success' causes the address to be created but the warehouse code
// to be silently discarded from the DB.
if (preg_match('/===\s*[\'"]success[\'"]/', $func_source)) {
    $errors[] = 'func.php still uses === \'success\' strict comparison — Torod returns boolean true.';
}

// ── Phone normalization ──

if (strpos($func_source, "preg_replace('/\D+/', '', \$phone)") === false) {
    $errors[] = 'order/create phone_number not stripped of non-digit characters.';
}

if (strpos($func_source, "preg_replace('/\D+/', '', \$company['phone']") === false
    && strpos($func_source, "preg_replace('/\D+/', '', \$company[\"phone\"]") === false) {
    $errors[] = 'warehouse create/address phone_number not stripped of non-digit characters.';
}

// ── Production URL ──

if (strpos($addon_xml, "https://api.torod.co/en/api") === false) {
    $errors[] = 'addon.xml production URL is not https://api.torod.co/en/api.';
}

// ── City ID persistence / fallback ──

if (strpos($func_source, 'function fn_torod_resolve_city_id_from_location') === false) {
    $errors[] = 'Torod func.php is missing fn_torod_resolve_city_id_from_location helper.';
}

if (strpos($torod_source, 'fn_torod_resolve_city_id_from_location') === false) {
    $errors[] = 'Torod realtime rates still do not call fn_torod_resolve_city_id_from_location.';
}

if (strpos($func_source, 'fn_torod_resolve_city_id_from_location($package_info_full[\'location\'])') === false
    && strpos($func_source, 'fn_torod_resolve_city_id_from_location($package_info_full["location"])') === false) {
    $errors[] = 'Torod order/create path still does not resolve city_id from package_info_full[location].';
}

// ── Realtime rates warehouse source ──

if (strpos($torod_source, "'warehouse' => \$this->_shipping_info['service_params']['courier_code'] ?? ''") !== false) {
    $errors[] = 'Torod realtime rates still send courier_code as warehouse instead of company warehouse_code.';
}

if (strpos($torod_source, 'warehouse_code') === false) {
    $errors[] = 'Torod realtime rates do not reference company warehouse_code.';
}

// ── Realtime rates courier filter compatibility ──

if (strpos($torod_source, "!empty(\$courier_code) && \$courier['id'] != \$courier_code") !== false) {
    $errors[] = 'Torod realtime rates still compare courier_code only to courier id, breaking configs saved as courier names like Aramex.';
}

if (strpos($torod_source, "strcasecmp(\$courier_name, \$courier_code)") === false) {
    $errors[] = 'Torod realtime rates do not support matching courier_code against courier name.';
}

// ── Realtime rates response field mapping ──

if (strpos($torod_source, "\$courier['price']") !== false && strpos($torod_source, "\$courier['rate']") === false) {
    $errors[] = 'Torod realtime rates still rely on courier[price] and do not fall back to Torod partner-list rate field.';
}

if (strpos($torod_source, "approximate_delivery_time") === false) {
    $errors[] = 'Torod realtime rates do not read Torod approximate_delivery_time field.';
}

// ── Success response message handling ──

if (strpos($torod_source, "if (!empty(\$response['message']))") !== false) {
    $errors[] = 'Torod processErrors still treats any response message as an error, including successful partner-list responses.';
}

if (strpos($torod_source, "empty(\$response['status'])") === false) {
    $errors[] = 'Torod processErrors do not guard response messages by unsuccessful status.';
}

// ── Report ──

if (!empty($errors)) {
    fwrite(STDOUT, "FAIL — " . count($errors) . " error(s):\n");
    foreach ($errors as $e) {
        fwrite(STDOUT, "  ✗ " . $e . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "PASS — All Torod API contract alignment checks passed.\n");
exit(0);
