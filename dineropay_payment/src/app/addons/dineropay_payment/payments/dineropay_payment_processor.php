<?php

// Preventing direct access to the script, because it must be included by the "include" directive. The "BOOTSTRAP" constant is declared during system initialization.
defined('BOOTSTRAP') or die('Access denied');

use Tygh\Http;
use Tygh\Registry;

$dispatch = dineropay_getInputData('dispatch');
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
    $orid      = dineropay_getInputData('orid', INPUT_GET);
    if (empty($orid)) {
        return; //it will return 404 Error
    }

    $orderId    = base64_decode($orid);
    $order_info = fn_get_order_info($orderId);
    if (!$order_info) {
        return;
    }

    if (!fn_check_payment_script('dineropay_payment_processor.php', $orderId)) {
        die('Access denied');
    }
    $merchantKey = Registry::get('addons.dineropay_payment.merchantKey');
    $merchantPassword = Registry::get('addons.dineropay_payment.merchantPassword');
    //redirect to success page if Paid or Complete
    if ($order_info['status'] == 'P' || $order_info['status'] == 'C') {
        fn_set_notification('N', __('congratulations'), __('text_order_saved_successfully'));
        fn_redirect('index.php?dispatch=checkout.complete&order_id=' . $orderId);
    }
    $payment = [];
    try {
        $hash = sha1(md5(strtoupper($orid.$merchantPassword)));
        // validate with api dineropay
        $url = "https://checkout.dineropay.com/api/v1/payment/status";
        $response = Http::post($url, json_encode([
            'merchant_key' => $merchantKey,
            'order_id' => $orid,
            'hash' => $hash
        ]),
            [
                'headers' => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);

        $payment = json_decode($response, true);
        if ($payment['status'] === 'settled' && $payment['order']['amount'] == $order_info['total']) {
            fn_change_order_status($orderId, 'P', '', fn_get_notification_rules([], false));
            fn_update_order_payment_info($orderId, $payment);
        } else {
            // todo should we handle the refund in here
            fn_change_order_status($orderId, 'F'); // gagal
        }
    } catch (Exception $ex) {
        $payment['reason_text'] = $ex->getMessage();
        fn_set_notification('E', __('Error'), $payment['reason_text']);
        dineropay_write_log(
            PHP_EOL.date('d.m.Y h:i:s')." - Order #$orderId ----- CallBack Exception is ".$payment['reason_text']
        );
    }
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

    // Merchant credentials from admin settings
    $merchantKey = Registry::get('addons.dineropay_payment.merchantKey');
    $merchantPassword = Registry::get('addons.dineropay_payment.merchantPassword');

    $order_id = base64_encode($order_info['order_id']);
    // Generate the success and cancel URLs
    $success_url = fn_url('index.php?dispatch=dineropay_payment.callback&status=success&orid='.$order_id);
    $cancel_url = fn_url('index.php?dispatch=dineropay_payment.callback&status=cancel&orid='.$order_id);

    // Prepare customer data
    $customer_name = $order_info['firstname'].' '.$order_info['lastname'];
    $customer_email = $order_info['email'];

    // Prepare billing address
    $country_code = ! empty($order_info['b_country']) ? $order_info['b_country'] : 'SA';
    $state = '';
    $address = ! empty($order_info['b_address']) ? $order_info['b_address'] : '';
    $city = ! empty($order_info['b_city']) ? $order_info['b_city'] : '';
    $zip = ! empty($order_info['b_zipcode']) ? $order_info['b_zipcode'] : '';
    $phone = ! empty($order_info['b_phone']) ? $order_info['b_phone'] : '';

    // Generate hash based on merchant password and order details
    $hash = sha1(md5(strtoupper($order_id. $order_info['total'].CART_PRIMARY_CURRENCY. 'Order #'.$order_id. $merchantPassword)));

    // Prepare the request data
    $request_data = [
        'merchant_key' => $merchantKey,
        'operation' => 'purchase',
        'methods' => ['card'],
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
        'url_target' => '',
        'req_token' => true,
        'hash' => $hash,
        'order' => [
            'number' => $order_id,
            'amount' => $order_info['total'],
            'currency' => CART_PRIMARY_CURRENCY,
            'description' => 'Order #'.$order_id
        ],
        'customer' => [
            'name' => $customer_name,
            'email' => $customer_email
        ],
        'billing_address' => [
            'country' => $country_code,
            'address' => $address,
            'city' => $city,
            'zip' => $zip,
            'phone' => $phone
        ]
    ];

    // Convert to JSON for API request
    $request_json = json_encode($request_data);

    // Make the API request to DineroPay
    $response = Http::post(
        'https://checkout.dineropay.com/api/v1/session',
        $request_json,
        [
            'headers' => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]
    );

    // Parse the response
    $response_data = json_decode($response, true);

    if ( ! empty($response_data) && ! empty($response_data['redirect_url'])) {
        // Redirect the customer to the DineroPay payment page
        fn_create_payment_form($response_data['redirect_url'], [], 'DineroPay', false, 'GET');
    } else {
        // Handle error
        $error_message = ! empty($response_data['message']) ? $response_data['message'] : __(
            'dineropay_connection_error'
        );
        fn_set_notification('E', __('error'), $error_message);
        fn_order_placement_routines('route', $order_id);
    }
}
