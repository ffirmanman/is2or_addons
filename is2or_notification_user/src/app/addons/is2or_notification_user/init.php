<?php

defined('BOOTSTRAP') or die('Access denied');

$func_file = __DIR__ . '/func.php';
if (is_file($func_file)) {
    require_once $func_file;
}

fn_register_hooks(
    'phpmailertransport_send_message_before_send'
);
