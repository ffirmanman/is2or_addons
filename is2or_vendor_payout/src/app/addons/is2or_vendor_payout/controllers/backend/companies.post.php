<?php

use Tygh\Registry;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied!');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'update') {
    $params = $_REQUEST;

    $company_data = Tygh::$app['view']->getTemplateVars('company_data');
    
    $params['company_id'] = $company_data['company_id'];
    list($vendor_banks, $vendor_banks_search) = fn_is2or_vendor_payout_get_vendor_banks(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page')
    );
    
    Tygh::$app['view']->assign([
        'vendor_banks' => $vendor_banks,
        'vendor_banks_search' => $vendor_banks_search
    ]);

    Registry::set('navigation.tabs.is2or_banks', [
        'title' => __('is2or_vendor_payout.bank_accounts'),
        'js' => true
    ]);
}

if ($mode == 'balance') {
    if ($company_id = Registry::get('runtime.company_id')) {
        list($total_balance, $pending_balance, $available_balance) = fn_is2or_vendor_payout_get_vendor_balances($company_id);

        list($vendor_banks) = fn_is2or_vendor_payout_get_vendor_banks(
            [
                'company_id' => $company_id,
                'status' => 'A'
            ]
        );

        Tygh::$app['view']->assign([
            'total_balance' => $total_balance,
            'pending_balance' => $pending_balance,
            'current_balance' => $available_balance,
            'vendor_banks' => $vendor_banks
        ]);
    } else {
        $vendor_payout_payment = fn_is2or_vendor_payout_get_payment();
        $payout_payment_available = fn_is2or_vendor_payout_payment_available($vendor_payout_payment);
        Tygh::$app['view']->assign([
            'vendor_payout_payment' => $vendor_payout_payment,
            'payout_payment_available' => $payout_payment_available
        ]);
    }

    // Inject is2or fields ke payouts
    $payouts = Tygh::$app['view']->getTemplateVars('payouts');
    if (!empty($payouts)) {
        $payout_ids = array_column($payouts, 'payout_id');
        $extra_data = db_get_hash_array(
            "SELECT payout_id, is2or_payout_details, is2or_payout_payment, is2or_payout_result
             FROM ?:vendor_payouts
             WHERE payout_id IN (?n)",
            'payout_id',
            $payout_ids
        );
        foreach ($payouts as &$payout) {
            $id = $payout['payout_id'];
            if (isset($extra_data[$id])) {
                $payout['is2or_payout_details'] = $extra_data[$id]['is2or_payout_details'];
                $payout['is2or_payout_payment'] = $extra_data[$id]['is2or_payout_payment'];
                $payout['is2or_payout_result']  = $extra_data[$id]['is2or_payout_result'];
            }
        }
        unset($payout);

        // Inject receipt data (bukti transfer) ke setiap payout
        $receipts = db_get_hash_array(
            "SELECT * FROM ?:is2or_payout_receipt WHERE payout_id IN (?n)",
            'payout_id',
            $payout_ids
        );
        foreach ($payouts as &$payout) {
            $id = $payout['payout_id'];
            if (isset($receipts[$id])) {
                $receipt = $receipts[$id];
                // Resolve URL gambar di controller agar template tidak perlu panggil PHP function
                $receipt['receipt_image_url'] = !empty($receipt['receipt_image'])
                    ? fn_is2or_vendor_payout_get_receipt_image_url($receipt['receipt_image'])
                    : '';
                $payout['is2or_receipt'] = $receipt;
            } else {
                $payout['is2or_receipt'] = [];
            }
        }
        unset($payout);

        Tygh::$app['view']->assign('payouts', $payouts);
    }
}
