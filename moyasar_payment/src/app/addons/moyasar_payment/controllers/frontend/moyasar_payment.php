<?php


use Tygh\Http;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode == 'callback') {
    $paymentId = moyasar_getInputData('id', INPUT_GET);
    $orid      = moyasar_getInputData('orid', INPUT_GET);
    if (empty($paymentId) || empty($orid)) {
        return; //it will return 404 Error
    }

    print_r('
<!DOCTYPE html>
<html lang="en-US">
    <head>
        <title>Processing Moyasar</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                font-family: sans-serif;
            }
            .loader {
              border: 13px solid #f3f3f3;
              border-radius: 50%;
              border-top: 13px solid #3498db;
              width: 50px;
              height: 50px;
              -webkit-animation: spin 2s linear infinite; /* Safari */
              animation: spin 2s linear infinite;
            }

            /* Safari */
            @-webkit-keyframes spin {
              0% { -webkit-transform: rotate(0deg);}
              100% { -webkit-transform: rotate(360deg);}
            }

            @keyframes spin {
              0% { transform: rotate(0deg);}
              100% { transform: rotate(360deg);}
            }
        </style>
    </head>
    <body>
        <center style="margin:10%">
            Please wait while your transaction <b>' . $paymentId . '</b> is processing...
            <br/><br/>Please do not refresh or close the window
            <br/><br/>
            <div class="loader"></div>
        </center>
        <script>window.location = "index.php?dispatch=payment_notification&payment=moyasar_payment_processor&orid=' . $orid . '&paymentId=' . $paymentId . '";</script>
    </body>
</html>');
    die;
}
//-----------------------------------------------------------------------------------------------------------------------------------------
else if ($mode == 'webhook') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawData = file_get_contents('php://input');
        $payload = json_decode($rawData, true);
        $secretWebhook = Registry::get('addons.moyasar_payment.secretTokenWebhook');
        if ($payload['data']['status'] == 'paid' && $payload['secret_token'] == $secretWebhook){
            $orderId = $payload['data']['metadata']['order_id'] ?? null;
            if ( ! fn_check_payment_script('moyasar_payment_processor.php', $orderId)) {
                die('Access denied');
            }
            if ($orderId) {
                $order_info = fn_get_order_info($orderId);
                fn_change_order_status($orderId, 'P', '', fn_get_notification_rules([], false));
                fn_update_order_payment_info($orderId, $payload['data']);
            }
        }
    }
    return [CONTROLLER_STATUS_OK];
} elseif ($mode == 'otp') {
    moyasar_write_log("OTP page loaded");;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $otp = $_REQUEST['otp'] ?? []; // array of digits

        $otp_digits = implode('', $otp);

        // Validate OTP input
        if (empty($otp_digits) || count($otp) != 6) {
            fn_set_notification('E', __('error'), __('stcpay.invalid_otp_format'));
            return [CONTROLLER_STATUS_REDIRECT, "moyasar_payment.otp"];
        }

        $order_id = $_SESSION['stcpay_order_id'] ?? 0;
        $stcpay_transaction_url = $_SESSION['stcpay_transaction_url'] ?? '';

        if (empty($order_id) || empty($stcpay_transaction_url)) {
            fn_set_notification('E', __('error'), __('stcpay.session_expired'));
            return [CONTROLLER_STATUS_REDIRECT, "checkout.checkout"];
        }

        try {
            $response = Http::get($stcpay_transaction_url.'&otp_value='.$otp_digits);
            $result = json_decode($response, true);

            if ($result && isset($result['status'])) {
                if ($result['status'] == 'paid') {
                    // Clear session data
                    $result['order_status'] = 'P';
                    fn_finish_payment($order_id, $result);

                    unset($_SESSION['stcpay_order_id']);
                    unset($_SESSION['stcpay_transaction_url']);
                    Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : [];
                    fn_set_notification('N', __('success'), __('stcpay.payment_successful'));
                    fn_clear_cart($_SESSION['cart'], true, true);
                    fn_order_placement_routines('route', $order_id, true);
                    //return [CONTROLLER_STATUS_OK, "checkout.complete&order_id=$order_id"];
                } else {
                    fn_set_notification('E', __('error'), $result['message'] ?? __('stcpay.invalid_otp'));
                    return [CONTROLLER_STATUS_REDIRECT, "moyasar_payment.otp"];
                }
            } else {
                fn_set_notification('E', __('error'), $result['message'] ?? __('stcpay.payment_error'));
                return [CONTROLLER_STATUS_REDIRECT, "moyasar_payment.otp"];
            }
        } catch (Exception $e) {
            fn_set_notification('E', __('error'), $result['message'] ?? __('stcpay.connection_error'));
            return [CONTROLLER_STATUS_REDIRECT, "moyasar_payment.otp"];
        }
    }

    $order_id = $_SESSION['stcpay_order_id'] ?? 0;
    $order_info = fn_get_order_info($order_id);
    $stcpay_transaction_url = $_SESSION['stcpay_transaction_url'] ?? '';

    if (empty($order_id) || empty($stcpay_transaction_url)) {
        fn_set_notification('E', __('error'), __('stcpay.session_expired'));
        return [CONTROLLER_STATUS_REDIRECT, "checkout.checkout"];
    }
    $currency = CART_PRIMARY_CURRENCY;
    // Ensure core scripts are loaded
    Tygh::$app['view']->assign('auth', Tygh::$app['session']['auth']);
    Tygh::$app['view']->assign('runtime', Registry::get('runtime'));
    Tygh::$app['view']->assign('order_info', $order_info);
    Tygh::$app['view']->assign('total', fn_format_price($order_info['total']));
    Tygh::$app['view']->assign('currency', $currency);
}


