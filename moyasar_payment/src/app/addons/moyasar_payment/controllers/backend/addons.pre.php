<?php

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update' && $_REQUEST['addon'] == 'moyasar_payment') {
        fn_moyasar_payment_update_settings($_REQUEST['mp_settings']);
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'update' && $_REQUEST['addon'] == 'moyasar_payment') {
    $mp_settings = fn_moyasar_payment_get_settings();
    Tygh::$app['view']->assign('mp_settings', $mp_settings);
}