<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('TRYOTO_API_URL', Registry::get('addons.tryoto_shipping.tryoto_api_url'));
fn_define('TRYOTO_API_TEST_URL', Registry::get('addons.tryoto_shipping.tryoto_dev_api_url'));
fn_define('TRYOTO_CLIENT_ID', Registry::get('addons.tryoto_shipping.tryoto_access_token'));
fn_define('TRYOTO_WEBHOOK_SECRET_KEY', Registry::get('addons.tryoto_shipping.tryoto_webhook_secret_key'));
fn_define('TRYOTO_COD_PAYMENT_METHOD_ID', Registry::get('addons.tryoto_shipping.tryoto_cod_payment_method_id'));
fn_define('TRYOTO_MODE', Registry::get('addons.tryoto_shipping.tryoto_shipping_mode'));

fn_define('TRYOTO_TRACKING_URL', "%s");


$service_types = array(
    'express' => 'Express',
    'sameDay' => 'Same day',
    'fastDelivery' => 'Fast Delivery',
    'coldDelivery' => 'Cold Delivery',
    'heavyAndBulky' => 'Heavy and Bulky',
    'electronicAndHeavy' => 'Electronic and Heavy'
);

$delivery_types = array(
    'toCustomerDoorstep' => 'To Customer Doorstep',
    'pickupByCustomer'  => 'Pickup by Customer',
    'toCustomerDoorstepOrPickupByCustomer' => 'To Customer Doorstep or Pickup by Customer'
);

fn_define('TRYOTO_DELIVERY_TYPES', json_encode($delivery_types));
fn_define('TRYOTO_SERVICE_TYPES', json_encode($service_types));
