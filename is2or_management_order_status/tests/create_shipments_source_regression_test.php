<?php

declare(strict_types=1);

$controller_path = dirname(__DIR__) . '/src/app/addons/is2or_management_order_status/controllers/backend/is2or_order_management.php';
$controller_source = file_get_contents($controller_path);

if ($controller_source === false) {
    fwrite(STDERR, "Failed to load is2or_order_management controller.\n");
    exit(1);
}

$errors = [];

if (strpos($controller_source, "order_info['shippings']") !== false || strpos($controller_source, '$order_info[\'shippings\']') !== false) {
    $errors[] = 'Bulk create_shipments still reads carrier from order_info[shippings] instead of order_info[shipping].';
}

if (strpos($controller_source, "?? 'tryoto'") !== false || strpos($controller_source, '?: \'tryoto\'') !== false) {
    $errors[] = 'Bulk create_shipments still defaults unresolved carriers to tryoto.';
}

if (strpos($controller_source, 'fn_get_shipping_info($shipping_id)') === false) {
    $errors[] = 'Bulk create_shipments does not fall back to fn_get_shipping_info($shipping_id) for carrier resolution.';
}

if (strpos($controller_source, "order_info['shipping']") === false && strpos($controller_source, '$order_info[\'shipping\']') === false) {
    $errors[] = 'Bulk create_shipments does not inspect order_info[shipping] for the selected carrier.';
}

if (!empty($errors)) {
    fwrite(STDOUT, "FAIL — " . count($errors) . " error(s):\n");
    foreach ($errors as $error) {
        fwrite(STDOUT, "  ✗ " . $error . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "PASS — Bulk create_shipments carrier resolution checks passed.\n");
exit(0);
