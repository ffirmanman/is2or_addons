<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('API2CART_URL', Registry::get('addons.api2cart.api_url'));
fn_define('API2CART_KEY', Registry::get('addons.api2cart.api_key'));
fn_define('API2CART_STORE_KEY', Registry::get('addons.api2cart.api_store_key'));
