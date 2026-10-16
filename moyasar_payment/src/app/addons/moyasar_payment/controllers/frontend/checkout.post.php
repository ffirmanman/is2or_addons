<?php

//refete to /var/www/cscart-v4.13.2/app/addons/stripe/controllers/frontend/checkout.post.php
////use Tygh\Addons\Stripe\ServiceProvider;
//
// Preventing direct access to the script, because it must be included by the "include" directive. The "BOOTSTRAP" constant is declared during system initialization.
defined('BOOTSTRAP') or die('Access denied');

use Tygh\Tygh;
use Tygh\Registry;

if ($mode === 'checkout') {
    $cart        = Tygh::$app['session']['cart'];
    $processor   = $cart['payment_method_data']['processor'] ?? null;
    if (strtolower($processor)==='moyasar') {
        $view = Tygh::$app['view'];
        try {
            $view->assign('publicKey', Registry::get('addons.moyasar_payment.publicKey'));
        } catch (Exception $ex) {
            $view->assign('mfError', $ex->getMessage());
        }
    }
}

return [CONTROLLER_STATUS_OK];
