<?php

defined('BOOTSTRAP') or die('Access denied');

if ($mode == 'callback') {
    $orid      = dineropay_getInputData('orid', INPUT_GET);
    $status = dineropay_getInputData('status', INPUT_GET);
    if (empty($orid)) {
        return; //it will return 404 Error
    }
    if ($status == 'cancel') {
        if ( ! empty($orid)) {
            $order_id = base64_decode($orid);
            fn_finish_payment($order_id, ['order_status' => 'F', 'reason_text' => __('dineropay_payment_canceled')]);
            fn_order_placement_routines('route', $order_id);
            return;
        }
    }

    print_r('
<!DOCTYPE html>
<html lang="en-US">
    <head>
        <title>Processing dineropay</title>
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
            Please wait while your transaction is processing...
            <br/><br/>Please do not refresh or close the window
            <br/><br/>
            <div class="loader"></div>
        </center>
        <script>window.location = "index.php?dispatch=payment_notification&payment=dineropay_payment_processor&orid='. $orid .'";</script>
    </body>
</html>');
    die;
}
//-----------------------------------------------------------------------------------------------------------------------------------------
else if ($mode == 'webhook') {
    // todo webhook
}
