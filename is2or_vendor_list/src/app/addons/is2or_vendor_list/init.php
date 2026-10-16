<?php

defined('BOOTSTRAP') or die('Access denied');

$func_file = __DIR__ . '/func.php';
if (is_file($func_file)) {
    require_once $func_file;
}

if (
    function_exists('fn_is2or_vendor_list_update_company_pre')
    && function_exists('fn_is2or_vendor_list_update_company')
) {
    fn_register_hooks(
        'update_company_pre',
        'update_company'
    );
}
