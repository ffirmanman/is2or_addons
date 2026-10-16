<?php

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied!');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'payouts_add' && $company_id = Registry::get('runtime.company_id')) {
        list(,,$available_balance) = fn_is2or_vendor_payout_get_vendor_balances($company_id);

        if ($available_balance < $_REQUEST['payment']['amount']) {
            fn_set_notification('E', __('error'), __('is2or_vendor_payout.insufficient_balance'));
            return [CONTROLLER_STATUS_REDIRECT, 'companies.balance'];
        }
    }
}