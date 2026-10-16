<?php

// Preventing direct access to the script, because it must be included by the "include" directive. The "BOOTSTRAP" constant is declared during system initialization.
defined('BOOTSTRAP') or die('Access denied');

use Tygh\Http;
use Tygh\Registry;

$dispatch = moyasar_getInputData('dispatch');
//-----------------------------------------------------------------------------------------------------------------------------------------
// Here are two different contexts for running the script.

if (defined('PAYMENT_NOTIFICATION')) {

    /**
     * Receiving and processing the answer
     * from third-party services and payment systems.
     *
     * Available variables:
     * @var string $mode The purpose of the request
     */
    $paymentId = moyasar_getInputData('paymentId', INPUT_GET);
    $orid      = moyasar_getInputData('orid', INPUT_GET);
    if (empty($paymentId) || empty($orid)) {
        return; //it will return 404 Error
    }

    $orderId    = base64_decode($orid);
    $order_info = fn_get_order_info($orderId);
    if (!$order_info) {
        return;
    }

    if (!fn_check_payment_script('moyasar_payment_processor.php', $orderId)) {
        die('Access denied');
    }
    $secret_key = Registry::get('addons.moyasar_payment.secretKey');
    //redirect to success page if Paid or Complete
    if ($order_info['status'] == 'P' || $order_info['status'] == 'C') {
        fn_set_notification('N', __('congratulations'), __('text_order_saved_successfully'));
        fn_redirect('index.php?dispatch=checkout.complete&order_id=' . $orderId);
    }
    $payment = [];
    try {
        // validate with api moyasar
        $url = "https://api.moyasar.com/v1/payments/{$paymentId}";
        $response = Http::get($url,[], [
            'headers' => array(
                'Authorization: Basic '.base64_encode($secret_key.':'),
                'Accept: application/json'
            )
        ]);

        $payment = json_decode($response, true);
        if ($payment['status'] === 'paid' && $payment['amount'] == round($order_info['total'] * 100)) {
            $payment['order_status'] = 'P';
            fn_finish_payment($orderId, $payment);
        } else {
            fn_change_order_status($orderId, 'F'); // gagal
        }
    } catch (Exception $ex) {
        $payment['reason_text'] = $ex->getMessage();
        fn_set_notification('E', __('Error'), $payment['reason_text']);
        moyasar_write_log(
            PHP_EOL.date('d.m.Y h:i:s')." - Order #$orderId ----- CallBack Exception is ".$payment['reason_text']
        );
    }

    if (isset($order_info['payment_info']['text_webhook_update'])) {
        $payment['text_webhook_update'] = 'No';
    }
    fn_clear_cart($_SESSION['cart'], true, true);
    fn_order_placement_routines('route', $orderId, true);
}
//-----------------------------------------------------------------------------------------------------------------------------------------
else {

    /**
     * Running the necessary logic for payment acceptance
     * after the customer presses the "Submit my order" button.
     *
     * available variables:
     *
     * @var array $order_info     Full information about the order
     * @var array $processor_data Information about the payment processor
     */
    $moyasar_method = moyasar_getInputData('moyasar_method');
    $orderId = $order_info['order_id'];
    $callBackURL = fn_url(
        'index.php?dispatch=moyasar_payment.callback&orid='.base64_encode($order_info['order_id'])
    );
    $secret_key = Registry::get('addons.moyasar_payment.secretKey');
    $public_key = Registry::get('addons.moyasar_payment.publicKey');
    fn_mark_payment_started($orderId);

    $url = "https://api.moyasar.com/v1/payments";
    $extra = [
        'headers' => [
            'Authorization: Basic '.base64_encode($secret_key.':'),
            'Accept: application/json'
        ]
    ];

    if ($moyasar_method == 'card') {
        $moyasarToken = moyasar_getInputData('moyasar_token');

        $dataPost = [
            'amount' => $order_info['total'] * 100,
            'currency' => 'SAR',
            'description' => '',
            'callback_url' => $callBackURL,
            'source' => [
                'type' => 'token',
                'token' => $moyasarToken
            ]
        ];
        $response = Http::post($url, $dataPost, $extra);
        $result = json_decode($response, true);
        if ($result['status'] != 'initiated') {
            moyasar_write_log(
                PHP_EOL.date(
                    'd.m.Y h:i:s'
                )." - Order #$orderId ----- Checkout Exception is ".$result['source']['message']
            );

            if ($dispatch == 'checkout.place_order') {
                fn_set_notification('E', __('Error'), $result['source']['message']);
            }
            fn_update_order_payment_info($orderId, $result);
            fn_order_placement_routines('route', $orderId);
        } else {
            fn_redirect($result['source']['transaction_url'], true);
        }
    }else if ($moyasar_method== 'stcpay') {
        $dataPost = [
            'publishable_api_key' => $public_key,
            'amount' => $order_info['total'] * 100,
            'currency' => 'SAR',
            'description' => '',
            'callback_url' => $callBackURL,
            'source' => [
                'type' => 'stcpay',
                "mobile"=> moyasar_getInputData('stc_mobile')
            ]
        ];
        $response = Http::post($url, $dataPost, $extra);
        $result = json_decode($response, true);
        moyasar_write_log($result);
        fn_update_order_payment_info($orderId, $result);
        if ($result['status'] != 'initiated') {
            moyasar_write_log(
                PHP_EOL.date(
                    'd.m.Y h:i:s'
                )." - Order #$orderId ----- Checkout Exception is ".$result['source']['message']
            );

            if ($dispatch == 'checkout.place_order') {
                fn_set_notification('E', __('Error'), $result['source']['message']);
            }
            fn_order_placement_routines('route', $orderId);
        } else {
            $redirect = $result['source']['transaction_url'];
            //fn_redirect($redirect, true);
            // simpan session otp
            $_SESSION['stcpay_order_id'] = $orderId;
            $_SESSION['stcpay_transaction_url'] = $redirect;
            fn_redirect("moyasar_payment.otp"); // redirect ke halaman input OTP
        }
    }else{
        fn_order_placement_routines('route', $orderId);
    }
}

//-----------------------------------------------------------------------------------------------------------------------------------------

//function mfSetError($orderId, $err, $dispatch) {
//    //reason_text
//    $pp_response['reason_text'] = $err;
//    fn_update_order_payment_info($orderId, $pp_response);
//
//    fn_set_notification('E', __('Error'), $err);
//    if ($dispatch == 'checkout.place_order') {
//        fn_redirect('index.php?dispatch=checkout.checkout');
//    }
//}
