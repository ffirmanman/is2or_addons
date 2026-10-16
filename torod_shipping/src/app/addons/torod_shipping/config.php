<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('TOROD_CLIENT_ID', Registry::get('addons.torod_shipping.torod_client_id'));
fn_define('TOROD_CLIENT_SECRET', Registry::get('addons.torod_shipping.torod_client_secret'));
fn_define('TOROD_API_URL', Registry::get('addons.torod_shipping.torod_api_url'));
fn_define('TOROD_PRODUCTION_API_URL', Registry::get('addons.torod_shipping.torod_production_api_url'));
fn_define('TOROD_MODE', Registry::get('addons.torod_shipping.torod_mode'));
fn_define('TOROD_WEBHOOK_SECRET_KEY', Registry::get('addons.torod_shipping.torod_webhook_secret_key'));
fn_define('TOROD_COD_PAYMENT_METHOD_ID', Registry::get('addons.torod_shipping.torod_cod_payment_method_id'));
fn_define('TOROD_TRACKING_URL', 'https://torod.co/tracking/%s');
fn_define('TOROD_API_TIMEOUT', 15);
fn_define('TOROD_QUOTE_VALIDITY_WINDOW', 900);

$torod_service_types = array(
    'normal' => 'Normal',
    'cold'   => 'Cold',
    'quick'  => 'Quick',
);
fn_define('TOROD_SERVICE_TYPES', json_encode($torod_service_types));
