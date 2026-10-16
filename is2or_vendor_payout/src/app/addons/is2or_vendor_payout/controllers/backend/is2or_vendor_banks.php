<?php

use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied!');

$company_id = $_REQUEST['company_id'] ?? 0;
if (!$company_id) return [CONTROLLER_STATUS_NO_PAGE];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $redirect_url = $_REQUEST['redirect_url'] ?? 'companies.update?company_id=' . $company_id . '&selected_section=is2or_banks';

    if ($mode == 'update') {
        $id = $_REQUEST['id'];
        $data = $_REQUEST['data'];

        if (!empty($data)) {
            $data['company_id'] = $company_id;

            fn_is2or_vendor_payout_update_vendor_bank($id, $data);
        }
    }

    if ($mode == 'delete') {
        $id = $_REQUEST['id'] ?? 0;

        if ($id) {
            fn_is2or_vendor_payout_delete_vendor_bank($id);
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('is2or_vendor_payout.text_bank_account_deleted'));
        }
    }

    if ($mode == 'm_update_statuses' && !empty($_REQUEST['status'])) {
        $ids = $_REQUEST['vendor_bank_ids'] ?? [];
        $status = $_REQUEST['status'];

        if (!empty($ids)) {
            foreach ($ids as $id) {
                fn_is2or_vendor_payout_update_vendor_bank($id, ['status' => $status]);
            }
        }
    }

    if ($mode == 'm_delete') {
        $ids = $_REQUEST['vendor_bank_ids'] ?? [];

        if (!empty($ids)) {
            foreach ($ids as $id) {
                fn_is2or_vendor_payout_delete_vendor_bank($id);
            }

            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('is2or_vendor_payout.text_bank_account_deleted'));
        }
    }

    return [CONTROLLER_STATUS_OK, $redirect_url];
}

if ($mode == 'add' || $mode == 'update') {
    $bank = [];

    if ($mode == 'update') {
        $bank_id = $_REQUEST['id'] ?? 0;

        $bank = fn_is2or_vendor_payout_get_vendor_bank($bank_id);

        if (!$bank) return [CONTROLLER_STATUS_NO_PAGE];
    }

    $countries = fn_get_simple_countries(true, CART_LANGUAGE);

    // Set default country
    if (empty($bank['country'])) {
        $company_data = fn_get_company_data($company_id);
        $bank['country'] = $company_data['country'];
    }

    Tygh::$app['view']->assign([
        'company_id' => $company_id,
        'countries' => $countries,
        'vendor_bank' => $bank
    ]);
}
