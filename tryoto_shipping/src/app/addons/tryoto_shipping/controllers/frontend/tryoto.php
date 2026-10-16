<?php

use Tygh\Registry;
use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);
    $signed_payload = $data['orderId'].':'.$data['status'].':'.$data['timestamp'];

    $signature = $data['signature'];
    $calculated_signature = base64_encode(hash_hmac('sha256', $signed_payload, TRYOTO_WEBHOOK_SECRET_KEY, true));
    if (!hash_equals($calculated_signature, $signature)) {
        fn_log_event('general', 'runtime', [
            'message' => 'signature invalid'
        ]);

        header("HTTP/1.1 403 OK");
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'status'  => 'failed',
            'message' => 'invalid signature'
        ]);

        exit;
    }
    if ($mode === 'create_order') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'create order received'
        ]);
        exit;
    }

    if ($mode == 'order_status_webhook') {
        if (!empty($data['status'])) {
            // Find shipment by order id
            $order_id = $data['orderId'];
            $shipment_id = 0;
            $id = 0;
            $tryoto_shipment = db_get_row("SELECT * FROM ?:tryoto_shipments WHERE order_id = ?s ORDER BY id DESC LIMIT 1", $data['orderId']);
            if(!empty($tryoto_shipment)) {
                $shipment_id = $tryoto_shipment['shipment_id'];
                $id = $tryoto_shipment['id'];
            }

            if ($shipment_id) {
                // Optional: update status in extra field
                if ($data['status'] == 'delivered') {
                    $mappingDelivered = Registry::get('addons.tryoto_shipping.webhook_delivered');

                    fn_tools_update_status([
                        'table' => 'shipments',
                        'status' => 'S', // S = Shipped
                        'id_name' => 'shipment_id',
                        'id' => $shipment_id,
                        'show_error_notice' => false
                    ]);
                    if ($mappingDelivered) {
                        fn_change_order_status($order_id, $mappingDelivered);
                    }

                } elseif ($data['status'] == 'pickedUp') {
                    fn_tools_update_status([
                        'table' => 'shipments',
                        'status' => 'P', // P = Picked up
                        'id_name' => 'shipment_id',
                        'id' => $shipment_id,
                        'show_error_notice' => false
                    ]);
                    $mappingPickedUp = Registry::get('addons.tryoto_shipping.webhook_picked_up');
                    if ($mappingPickedUp) {
                        fn_change_order_status($order_id, $mappingPickedUp);
                    }

                } elseif ($data['status'] == 'shipmentCanceled' || $data['status'] == 'canceled') {
                    // delete shipment
                    $result = db_query('DELETE FROM ?:shipments WHERE shipment_id = ?i', $shipment_id);
                    db_query('DELETE FROM ?:shipment_items WHERE shipment_id = ?i', $shipment_id);

                    $mappingCancelled = Registry::get('addons.tryoto_shipping.webhook_shipment_cancelled');
                    if ($mappingCancelled) {
                        fn_change_order_status($order_id, $mappingCancelled);
                    }
                }  elseif ($data['status'] == 'searchingDriver') {
                    $_data = array(
                        'tryoto_shipping_id' => isset($data['trackingNumber']) ? strval($data['trackingNumber']) : '',
                        'delivery_tracking_url' => $data['trackingUrl'] ?? '',
                        'awb_url' => $data['printAWBURL'] ?? '',
                        'total_value' => $data['totalValue'] ?? 0,
                        'delivery_company' => $data['deliveryCompany'] ?? '',
                        'status' => $data['status'],
                        'tryoto_id' => strval($data['otoId']),
                    );
                    db_query('UPDATE ?:tryoto_shipments set ?u WHERE shipment_id = ?i', $_data, $shipment_id);
                    db_query('UPDATE ?:shipments set tracking_number = ?s WHERE shipment_id = ?i', $data['trackingNumber'], $shipment_id);

                    $mappingStatus = Registry::get('addons.tryoto_shipping.webhook_searching_driver');
                    if ($mappingStatus) {
                        fn_change_order_status($order_id, $mappingStatus);
                    }
                }else if($data['status'] == 'shipmentCreated') {
                    $_data = array(
                        'tryoto_shipping_id' => isset($data['trackingNumber']) ? strval($data['trackingNumber']) : '',
                        'delivery_tracking_url' => $data['trackingUrl'] ?? '',
                        'awb_url' => $data['printAWBURL'] ?? '',
                        'total_value' => $data['totalValue'] ?? 0,
                        'delivery_company' => $data['deliveryCompany'] ?? '',
                        'status' => $data['status'],
                        'tryoto_id' => strval($data['otoId']),
                    );
                    db_query('UPDATE ?:tryoto_shipments set ?u WHERE shipment_id = ?i', $_data, $shipment_id);
                    db_query('UPDATE ?:shipments set tracking_number = ?s WHERE shipment_id = ?i', $data['trackingNumber'], $shipment_id);

                    $mappingStatus = Registry::get('addons.tryoto_shipping.webhook_shipment_created');
                    if ($mappingStatus) {
                        fn_change_order_status($order_id, $mappingStatus);
                    }
                }
                if($id && $data['status'] != 'searchingDriver') {
                    db_query('UPDATE ?:tryoto_shipments set status = ?s WHERE id = ?i', $data['status'], $id);
                }

                header('Content-Type: application/json; charset=utf-8');
                http_response_code(200);
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Updated status to: '.$data['status'].''
                ]);
                exit;
            }else{
                fn_log_event('general', 'runtime', [
                    'message' => 'invalid shipment'
                ]);

                header("HTTP/1.1 403 OK");
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode([
                    'status'  => 'failed',
                    'message' => 'invalid shipment'
                ]);
                exit;
            }
        }
    }

    if ($mode == 'delivery_error_webhook') {
        // Find shipment by order id
        $shipment_id = 0;
        $id = 0;
        $tryoto_shipment = db_get_row("SELECT * FROM ?:tryoto_shipments WHERE order_id = ?s ORDER BY id DESC LIMIT 1", $data['orderId']);
        if(!empty($tryoto_shipment)) {
            $shipment_id = $tryoto_shipment['shipment_id'];
            $id = $tryoto_shipment['id'];

            list($shipments,) = fn_get_shipments_info(array('shipment_id' => $shipment_id));
            $order_id = 0;
            if (!empty($shipments)) {
                foreach ($shipments as $shipment) {
                    $order_id = $shipment['order_id'];
                }
            }

            // delete shipment
            $result = db_query('DELETE FROM ?:shipments WHERE shipment_id = ?i', $shipment_id);
            db_query('DELETE FROM ?:shipment_items WHERE shipment_id = ?i', $shipment_id);


            db_query('UPDATE ?:tryoto_shipments set status = ?s WHERE id = ?i', $data['errorCode'], $id);
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode([
            'status'  => 'failed',
            'message' => 'delivery_error_webhook'
        ]);
        exit;

    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode([
        'status'  => 'failed',
        'message' => 'webhook not defined'
    ]);
    exit;
}
