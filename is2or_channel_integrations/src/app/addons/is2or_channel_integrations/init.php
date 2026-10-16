<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_register_hooks(
    'update_product_post',
    'delete_product_post',
    'change_order_status'
);