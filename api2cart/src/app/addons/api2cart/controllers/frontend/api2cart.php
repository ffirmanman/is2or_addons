<?php

use Tygh\Registry;
use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode == 'order_webhook') {
    // Ambil header
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (strpos($name, 'HTTP_') === 0) {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }

    $rawData = file_get_contents('php://input');
    $payload = json_decode($rawData, true);

    // Validasi Signature
    $calculatedSignature = calcSign($headers, $rawData);
    $signatureFromRequest = $headers['X-Webhook-Signature'] ?? '';

    if ($calculatedSignature !== $signatureFromRequest) {
        fn_log_event('general', 'error', ['message' => 'Invalid webhook signature']);
        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    // Validasi hanya process order add
    if (
        ($headers['X-Webhook-Entity'] ?? '') === 'order' &&
        ($headers['X-Webhook-Action'] ?? '') === 'add'
    ) {
        $order_id_from_webhook = $payload['id'];

        // Call API2Cart untuk ambil data detail order
        $url = API2CART_URL.'/order.info.json?id='.$order_id_from_webhook;

        $response = Http::get($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => API2CART_KEY,
                'x-store-key' => API2CART_STORE_KEY,
            ]
        ]);

        $result = json_decode($response, true);

        if ( ! empty($result['result'])) {
            $order_data = $result['result'];
            fn_process_api2cart_order($order_data);
        }
    }

    return [CONTROLLER_STATUS_OK];
}
