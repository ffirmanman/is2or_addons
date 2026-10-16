<?php

use IS2OR\MoyasarPayment\Api;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'create_payout' && !empty($_REQUEST['iban'])) {
        $api = new Api();
        list($success, $msg) = $api->createPayoutAccount($_REQUEST['iban']);

        if ($success) {
            fn_set_notification('N', __('notice'), $msg ?? __('moyasar_payment.create_payout_success'));
        } else {
            fn_set_notification('E', __('error'), $msg ?? __('moyasar_payment.create_payout_error'));
        }

        Tygh::$app['ajax']->assign('mp_settings', fn_moyasar_payment_get_settings());
    }
}