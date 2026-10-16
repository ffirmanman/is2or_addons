<?php

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied!');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ------------------------------------------------------------------
    // Mode: payout — proses pembayaran via payment gateway
    // ------------------------------------------------------------------
    if ($mode == 'payout') {
        $payout_id = $_REQUEST['payout_id'] ?? 0;

        if ($payout_id) {
            $payment = fn_is2or_vendor_payout_get_payment();
            if (fn_is2or_vendor_payout_payment_available($payment)) {
                list($success, $message) = fn_is2or_vendor_payout_process_payment($payout_id, $payment);

                if ($success !== false) {
                    fn_set_notification('N', __('notice'), $message ?: __('is2or_vendor_payout.payout_success'));
                } else {
                    fn_set_notification('E', __('error'), $message ?: __('is2or_vendor_payout.payout_failed'));
                }
            }
        }

        return [CONTROLLER_STATUS_OK, 'companies.balance?selected_section=withdrawals'];
    }

    // ------------------------------------------------------------------
    // Mode: fetch_details — tarik detail pembayaran dari gateway
    // ------------------------------------------------------------------
    if ($mode == 'fetch_details') {
        $payout_id = $_REQUEST['payout_id'] ?? 0;

        list($success, $message) = fn_is2or_vendor_payout_get_payout_payment_details($payout_id);
        if ($success !== false) {
            fn_set_notification('N', __('notice'), $message ?: __('is2or_vendor_payout.update_details_success'));
        } else {
            fn_set_notification('E', __('error'), $message ?: __('is2or_vendor_payout.update_details_failed'));
        }

        return [CONTROLLER_STATUS_OK, 'is2or_vendor_payout.detail?payout_id=' . $payout_id];
    }

    // ------------------------------------------------------------------
    // Mode: save_receipt — simpan bukti transfer (upsert)
    // ------------------------------------------------------------------
    if ($mode == 'save_receipt') {
        $payout_id = (int) ($_REQUEST['payout_id'] ?? 0);
        $order_id  = (int) ($_REQUEST['order_id']  ?? 0);

        if (!$payout_id) {
            fn_set_notification('E', __('error'), __('is2or_vendor_payout.receipt_invalid_payout'));
            exit;
        }

        // Validasi payout ada & ambil data menggunakan SELECT * agar kolom yang dicari pasti ada
        $payout = db_get_row("SELECT * FROM ?:vendor_payouts WHERE payout_id = ?i", $payout_id);
        if (!$payout) {
            fn_set_notification('E', __('error'), __('is2or_vendor_payout.receipt_invalid_payout'));
            exit;
        }

        // Gunakan order_id dari payout jika tidak dikirim via form
        if (!$order_id && isset($payout['order_id'])) {
            $order_id = (int) $payout['order_id'];
        }

        // Sanitasi data teks dari form
        $receipt_data = [
            'company_id'        => (int) $payout['company_id'],
            'order_id'          => $order_id,
            'bank_name'         => strip_tags($_REQUEST['receipt']['bank_name'] ?? ''),
            'account_name'      => strip_tags($_REQUEST['receipt']['account_name'] ?? ''),
            'iban'              => strip_tags($_REQUEST['receipt']['iban'] ?? ''),
            'transaction_value' => (float) ($_REQUEST['receipt']['transaction_value'] ?? 0),
            'comment'           => strip_tags($_REQUEST['receipt']['comment'] ?? ''),
            'approval_status'   => strip_tags($_REQUEST['receipt']['approval_status'] ?? ''),
        ];

        // Handle upload gambar dengan memastikan error status adalah UPLOAD_ERR_OK
        if (!empty($_FILES['receipt_image']['name']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $image_path = fn_is2or_vendor_payout_upload_receipt_image(
                $_FILES['receipt_image'],
                $payout_id
            );
            if ($image_path !== false) {
                $receipt_data['receipt_image'] = $image_path;
            }
        }

        $result = fn_is2or_vendor_payout_save_receipt($payout_id, $receipt_data);

        if ($result) {
            fn_set_notification('N', __('notice'), __('is2or_vendor_payout.receipt_saved'));
        } else {
            fn_set_notification('E', __('error'), __('is2or_vendor_payout.receipt_save_failed'));
        }

        exit;
    }
}

// ------------------------------------------------------------------
// Mode: detail — halaman detail payout
// ------------------------------------------------------------------
if ($mode == 'detail') {
    $payout_id = $_REQUEST['payout_id'] ?? 0;

    $payout = db_get_row("SELECT * FROM ?:vendor_payouts WHERE payout_id = ?i", $payout_id);
    if (!$payout) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $payout['is2or_payout_result'] = !empty($payout['is2or_payout_result'])
        ? @unserialize($payout['is2or_payout_result'])
        : [];

    if (!is_array($payout['is2or_payout_result'])) {
        $payout['is2or_payout_result'] = [];
    }

    $update_details_available = fn_is2or_vendor_payout_payment_details_available($payout);

    Tygh::$app['view']->assign([
        'payout'                   => $payout,
        'update_details_available' => $update_details_available,
    ]);
}

// ------------------------------------------------------------------
// Mode: bank_payment_info — popup info bank + form bukti transfer
// ------------------------------------------------------------------
if ($mode == 'bank_payment_info') {
    $payout_id = $_REQUEST['payout_id'] ?? 0;

    $payout = db_get_row("SELECT * FROM ?:vendor_payouts WHERE payout_id = ?i", $payout_id);
    if (!$payout) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    // Decode bank details dari is2or_payout_details
    $bank_details = [];
    if (!empty($payout['is2or_payout_details'])) {
        $raw = $payout['is2or_payout_details'];

        $try = @unserialize($raw);
        if (is_array($try) && !empty($try)) {
            $bank_details = $try;
        } else {
            $decoded = base64_decode($raw, true);
            if ($decoded !== false) {
                $try2 = @unserialize($decoded);
                if (is_array($try2) && !empty($try2)) {
                    $bank_details = $try2;
                }
            }
        }
    }

    $company = db_get_row(
        "SELECT * FROM ?:companies WHERE company_id = ?i",
        $payout['company_id']
    );

    $existing_receipt = fn_is2or_vendor_payout_get_receipt($payout_id);

    $receipt_image_url = '';
    if (!empty($existing_receipt['receipt_image'])) {
        $receipt_image_url = fn_is2or_vendor_payout_get_receipt_image_url(
            $existing_receipt['receipt_image']
        );
    }

    Tygh::$app['view']->assign([
        'payout'             => $payout,
        'bank_details'       => $bank_details,
        'company'            => $company,
        'existing_receipt'   => $existing_receipt,
        'receipt_image_url'  => $receipt_image_url,
    ]);
}