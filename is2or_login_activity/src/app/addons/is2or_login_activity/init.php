<?php

defined('BOOTSTRAP') or die('Access denied');

$func_file = __DIR__ . '/func.php';
if (is_file($func_file)) {
    require_once $func_file;
}

fn_register_hooks(
    'sucess_user_login',
    'dispatch_before_send_response',
    'user_logout'
);
