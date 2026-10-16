<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Mendaftarkan hook update_product_post
fn_register_hooks(
    'update_product_post',
    'get_products'
);