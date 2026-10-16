<?php

use IS2OR\MoyasarPayment\Api;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Settings;

defined('AREA') or die('Access denied');
defined('BOOTSTRAP') or die('Access denied');

define('MOYASAR_PATH', dirname(__FILE__));

require_once MOYASAR_PATH . '/moyasar_payment.php';

function fn_moyasar_payment_get_settings() {
    return Settings::instance()->getValues('moyasar_payment', 'ADDON', false);
}

function fn_moyasar_payment_update_settings($data) {
    foreach($data as $key => $value) {
        Settings::instance()->updateValue($key, $value, 'moyasar_payment');
    }
}

/* Vendor Payout Compatibility Functions */
function fn_moyasar_payment_is2or_vendor_payout_process($payout) {
    $source_id = Registry::ifGet('addons.moyasar_payment.payout_id', false);

    if (!$source_id) {
        return [
            false,
            __('moayasar_payment.payout_account_not_configured')
        ];
    }

    $result = false;
    $message = '';
    if (
        !empty($payout)
        && !empty($payout['is2or_payout_details'])
    ) {
        $bank = $payout['is2or_payout_details'];

        $api = new Api;
        $data = [
            'source_id' => $source_id,
            'amount' => intval($payout['payout_amount']),
            'purpose' => 'payment_to_merchant',
            'destination' => [
                'type' => 'bank',
                'iban' => $bank['iban'],
                'name' => $bank['account_name'],
                'mobile' => $bank['mobile'],
                'country' => $bank['country'],
                'city' => $bank['city']
            ]
        ];

        list($result, $message) = $api->createPayout($data);
    }

    return [$result, $message];
}

function fn_moyasar_payment_is2or_vendor_payout_details($payout) {
    $result = false;
    $message = '';
    
    if (!empty($payout['is2or_payout_result']) && !empty($payout['is2or_payout_result']['id'])) {
        $moyasar_payout_id = $payout['is2or_payout_result']['id'];
        
        $api = new Api;
        list($result, $message) = $api->getPayout($moyasar_payout_id);
    }

    return [$result, $message];
}
/* Vendor Payout Compatibility Functions */

function fn_moyasar_payment_place_order(&$order_id, &$action, &$order_status) {

    $dispatch = moyasar_getRequestData('dispatch');
    if ($dispatch != 'order_management.place_order.save') {
        return;
    }

    $mfPaymentId = moyasar_getRequestData('payment_id');
    if (empty($mfPaymentId)) {
        return;
    }

    $payment = fn_get_payment_method_data((int) $mfPaymentId);
    if (str_contains(strtolower($payment['processor']), 'moyasar')) {
        $action = '';
    }
}

function fn_moyasar_payment_change_order_status(
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
    if ($processor_script !== 'moyasar_payment_processor.php') {
        return;
    }

    // Refund hanya jika status berubah ke Cancel (I = Canceled)
    if ($status_to === 'I' && $status_from !== 'I') {
        $secret_key = Registry::get('addons.moyasar_payment.secretKey');

        // Payment reference dari DineroPay (disimpan waktu transaksi)
        $paymentId = $order_info['payment_info']['id'] ?? null;
        if ($paymentId) {
            $dataPost = [
                'amount' => $order_info['total'] * 100,
            ];

            try{
                $url = "https://api.moyasar.com/v1/payments/{$paymentId}/refund";
                $response = Http::post($url, json_encode($dataPost), [
                    'headers' => [
                        'Authorization: Basic '.base64_encode($secret_key.':'),
                        'Accept: application/json'
                    ]
                ]);

                // Simpan log response agar bisa dicek
                fn_log_event('general', 'runtime', [
                    'message' => 'moyasar refund response for order '.$order_info['order_id'].': '.print_r(
                            $response,
                            true
                        ),
                ]);

                $statusCode = Http::getStatus();
                $result = json_decode($response, true);
                if ($statusCode >= 200 && $statusCode < 300) {
                    // sucess
                    fn_set_notification(
                        'N',
                        __('notice'),
                        __('Refund processed successfully for order #').$order_info['order_id']
                    );
                }else{
                    fn_set_notification(
                        'N',
                        __('notice'),
                        __('Refund processed successfully for order #').$order_info['order_id'].': '.$result['message']
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
