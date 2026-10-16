<?php

use Tygh\Http;
use Tygh\Registry;

defined('AREA') or die('Access denied');
defined('BOOTSTRAP') or die('Access denied');

define('DINEROPAY_PATH', dirname(__FILE__));

require_once DINEROPAY_PATH . '/dineropay_payment.php';


function fn_dineropay_payment_place_order(&$order_id, &$action, &$order_status) {

    $dispatch = dineropay_getRequestData('dispatch');
    if ($dispatch != 'order_management.place_order.save') {
        return;
    }

    $mfPaymentId = dineropay_getRequestData('payment_id');
    if (empty($mfPaymentId)) {
        return;
    }

    $payment = fn_get_payment_method_data((int) $mfPaymentId);
    if (str_contains(strtolower($payment['processor']), 'dineropay')) {
        $action = '';
    }
}

function fn_dineropay_payment_change_order_status(
    $status_to,
    $status_from,
    $order_info,
    $force_notification,
    $order_statuses,
    $place_order
) {
    // Jangan proses untuk parent order
    if ($order_info['is_parent_order'] === 'Y') {
        return;
    }

    // Ambil payment_id dari order
    $payment_id = $order_info['payment_id'] ?? 0;
    if ( ! $payment_id) {
        return;
    }

    // Ambil processor_script
    $processor_script = db_get_field(
        "SELECT pp.processor_script 
         FROM ?:payments p 
         JOIN ?:payment_processors pp ON p.processor_id = pp.processor_id 
         WHERE p.payment_id = ?i",
        $payment_id
    );

    // Pastikan pakai DineroPay
    if ($processor_script !== 'dineropay_payment_processor.php') {
        return;
    }

    // Refund hanya jika status berubah ke Cancel (I = Canceled)
    if ($status_to === 'I' && $status_from !== 'I') {
        $merchantKey = Registry::get('addons.dineropay_payment.merchantKey');
        $merchantPassword = Registry::get('addons.dineropay_payment.merchantPassword');

        // Payment reference dari DineroPay (disimpan waktu transaksi)
        $dinero_payment_id = $order_info['payment_info']['payment_id'] ?? null;

        if ($dinero_payment_id) {
            $amount = $order_info['total']; // hanya total order vendor ini

            $hash = sha1(
                md5(
                    strtoupper($dinero_payment_id.$amount.$merchantPassword)
                )
            );

            try{
                $url = "https://checkout.dineropay.com/api/v1/payment/refund";

                $response = Http::post(
                    $url,
                    json_encode([
                        'merchant_key' => $merchantKey,
                        'payment_id' => $dinero_payment_id,
                        'hash' => $hash,
                        'amount' => $amount
                    ]),
                    [
                        'headers' => [
                            'Content-Type: application/json',
                            'Accept' => 'application/json'
                        ]
                    ]
                );

                // Simpan log response agar bisa dicek
                fn_log_event('general', 'runtime', [
                    'message' => 'DineroPay refund response for order '.$order_info['order_id'].': '.print_r(
                            $response,
                            true
                        ),
                ]);
                $statusCode = Http::getStatus();
                $result = json_decode($response, true);

                if ($statusCode >= 200 && $statusCode < 300) {
                    fn_set_notification(
                        'N',
                        __('notice'),
                        __('Refund processed successfully for order #').$order_info['order_id']
                    );
                } else {
                    $message = $result['message'] ?? 'Unknown error';
                    fn_set_notification(
                        'N',
                        __('notice'),
                        __('Refund processed successfully for order #').$order_info['order_id'].': '.$message
                    );
                }
            }catch (Exception $ex){
                fn_set_notification(
                    'E',
                    __('error'),
                    __('Refund failed for order #').$order_info['order_id'].': '.$ex->getMessage()
                );
            }
        }
    }
}


//-----------------------------------------------------------------------------------------------------------------------------------------
