<?php

defined('BOOTSTRAP') or die('Access denied!');

fn_register_hooks(
    'vendor_payouts_update',
    'vendor_payouts_update_pre',
    'vendor_payouts_get_income' 
);

if (function_exists('fn_is2or_vendor_payout_install')) {
    fn_is2or_vendor_payout_install();
}