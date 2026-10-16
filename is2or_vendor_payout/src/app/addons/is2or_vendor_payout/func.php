<?php

use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\VendorPayouts;

defined('BOOTSTRAP') or die('Access denied!');

function fn_settings_variants_addons_is2or_vendor_payout_payment_method() {
    $payments = db_get_hash_array("SELECT * FROM ?:is2or_vendor_payout_payments", 'id');

    $data = [];
    foreach ($payments as $id => $payment) {
        $data[$id] = $payment['name'];
    }

    return $data;
}

function fn_is2or_vendor_payout_get_payment($payment_id = null) {
    if (!$payment_id) {
        $payment_id = Registry::get('addons.is2or_vendor_payout.payment_method');
    }

    $payment = db_get_row("SELECT * FROM ?:is2or_vendor_payout_payments WHERE id = ?i", $payment_id);

    return $payment;
}

function fn_is2or_vendor_payout_payment_available($vendor_payout_payment = null) {
    if (!$vendor_payout_payment) {
        $vendor_payout_payment = fn_is2or_vendor_payout_get_payment();
    }

    if ($vendor_payout_payment) {
        $function_name = fn_is2or_vendor_payout_get_payment_function_name($vendor_payout_payment['addon']);

        if (function_exists($function_name)) {
            return true;
        }
    }

    return false;
}

function fn_is2or_vendor_payout_get_payment_function_name($addon) {
    return 'fn_' . $addon . '_is2or_vendor_payout_process';
}

function fn_is2or_vendor_payout_process_payment($payout_id, $payment) {
    $result = false;
    $message = '';

    $function_name = fn_is2or_vendor_payout_get_payment_function_name($payment['addon']);
    if (function_exists($function_name)) {
        $payout = fn_is2or_vendor_payout_get_payout_details($payout_id);

        if (!empty($payout) && !empty($payout['is2or_payout_details'])) {

            list($result, $message) = call_user_func($function_name, $payout);

            if ($result !== false) {
                db_query("UPDATE ?:vendor_payouts SET ?u WHERE payout_id = ?i", [
                    'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
                    'is2or_payout_payment' => Registry::get('addons.is2or_vendor_payout.payment_method'),
                    'is2or_payout_result' => serialize($result),
                ], $payout_id);
            }
        }
    }

    return [$result, $message];
}

function fn_is2or_vendor_payout_payment_details_available($payout) {
    if ($payout['is2or_payout_payment']) {
        $payment = fn_is2or_vendor_payout_get_payment($payout['is2or_payout_payment']);

        if ($payment) {
            $function_name = fn_is2or_vendor_payout_get_details_function_name($payment['addon']);

            if (function_exists($function_name)) {
                return true;
            }
        }
    }

    return false;
}

function fn_is2or_vendor_payout_get_details_function_name($addon) {
    return 'fn_' . $addon . '_is2or_vendor_payout_details';
}

function fn_is2or_vendor_payout_get_payout_payment_details($payout_id) {
    $result = false;
    $message = '';

    $payout = fn_is2or_vendor_payout_get_payout_details($payout_id);

    if (!empty($payout) && !empty($payout['is2or_payout_payment'])) {
        $payment = fn_is2or_vendor_payout_get_payment($payout['is2or_payout_payment']);

        if (!empty($payment)) {
            $function_name = fn_is2or_vendor_payout_get_details_function_name($payment['addon']);

            if (function_exists($function_name)) {
                list($result, $message) = call_user_func($function_name, $payout);

                if ($result !== false) {
                    db_query("UPDATE ?:vendor_payouts SET ?u WHERE payout_id = ?i", [
                        'is2or_payout_result' => serialize($result),
                    ], $payout_id);
                }
            }
        }
    }

    return [$result, $message];
}

function fn_is2or_vendor_payout_get_payout_details($payout_id) {
    $payout = db_get_row("SELECT * FROM ?:vendor_payouts WHERE payout_id = ?i", $payout_id);

    if (!empty($payout)) {
        $payout['is2or_payout_details'] = !empty($payout['is2or_payout_details'])
            ? unserialize($payout['is2or_payout_details'])
            : [];

        $payout['is2or_payout_result'] = !empty($payout['is2or_payout_result'])
            ? unserialize($payout['is2or_payout_result'])
            : [];

    }

    return $payout;
}

function fn_is2or_vendor_payout_get_vendor_banks($params = [], $items_per_page = 0) {
    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page,
    ];
    $params = array_merge($default_params, $params);

    $sortings = [
        'country' => '?:is2or_vendor_banks.country',
        'bank_name' => '?:is2or_vendor_banks.bank_name',
        'account_name' => '?:is2or_vendor_banks.account_name',
        'account_number' => '?:is2or_vendor_banks.account_number',
        'swift_code' => '?:is2or_vendor_banks.swift_code',
        'iban' => '?:is2or_vendor_banks.iban',
        'status' => '?:is2or_vendor_banks.status',
    ];

    $sorting = db_sort($params, $sortings, 'country', 'asc');

    $condition = $limit = $join = '';

    $fields = [
        '?:is2or_vendor_banks.*',
    ];

    if (!empty($params['company_id'])) {
        $condition .= db_quote(' AND ?:is2or_vendor_banks.company_id = ?i', $params['company_id']);
    }

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:is2or_vendor_banks $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $data = db_get_hash_array(
        "SELECT ?p FROM ?:is2or_vendor_banks " .
        $join .
        "WHERE 1 ?p ?p ?p",
        'id', implode(', ', $fields), $condition, $sorting, $limit
    );

    return [$data, $params];
}

function fn_is2or_vendor_payout_get_vendor_bank($id = 0) {
    $data = db_get_row("SELECT * FROM ?:is2or_vendor_banks WHERE id = ?i", $id);

    return $data;
}

function fn_is2or_vendor_payout_update_vendor_bank($id, $data) {
    if ($id) {
        db_query("UPDATE ?:is2or_vendor_banks SET ?u WHERE id = ?i", $data, $id);
    } else {
        $id = db_query("INSERT INTO ?:is2or_vendor_banks ?e", $data);
    }

    return $id;
}

function fn_is2or_vendor_payout_delete_vendor_bank($id) {
    db_query("DELETE FROM ?:is2or_vendor_banks WHERE id = ?i", $id);
}

function fn_is2or_vendor_payout_get_vendor_balances($company_id) {
    $vendor_payouts = VendorPayouts::instance(['vendor' => $company_id]);

    list($total_balance) = $vendor_payouts->getBalance();
    $pending_balance = fn_is2or_vendor_payout_get_pending_balance($company_id, $vendor_payouts);
    $available_balance = $total_balance - $pending_balance;

    return [
        $total_balance,
        $pending_balance,
        $available_balance
    ];
}

function fn_is2or_vendor_payout_get_pending_balance($company_id, $vendor_payouts = null) {
    if (!$vendor_payouts instanceof VendorPayouts) {
        $vendor_payouts = VendorPayouts::instance(['vendor' => $company_id]);
    }

    return db_get_field(
        "SELECT SUM(order_amount-commission_amount) FROM ?:vendor_payouts vp
            LEFT JOIN ?:orders o ON o.order_id = vp.order_id
            WHERE o.status IN (?a)
                AND vp.company_id = ?i
                AND payout_type = ?s
                AND approval_status = ?s",
        $vendor_payouts->getPayoutOrderStatuses(),
        $company_id,
        VendorPayoutTypes::ORDER_PLACED,
        VendorPayoutApprovalStatuses::PENDING
    );
}

function fn_is2or_vendor_payout_complete_order($order_id) {
    $userId = Tygh::$app['session']['auth']['user_id'];
    $affected_rows = db_query("UPDATE ?:orders SET status= ?s, is2or_customer_completed = ?s WHERE order_id = ?i AND user_id= ?i",
        'C',
        YesNo::YES,
        $order_id, $userId);
    if ($affected_rows) {
        db_query("UPDATE ?:vendor_payouts SET approval_status = ?s WHERE order_id = ?i", VendorPayoutApprovalStatuses::COMPLETED, $order_id);
    }
}

function fn_is2or_vendor_payout_auto_complete_orders() {
    $auto_complete_days = (int) Registry::ifGet('addons.is2or_vendor_payout.auto_complete_days', 1);
    $statusShouldBeCheck = Registry::ifGet('addons.is2or_vendor_payout.show_confirm_button_status');

    $affected_rows = db_query(
        "UPDATE ?:orders 
         SET is2or_customer_completed = ?s , status = ?s
         WHERE status= ?s
            AND is2or_customer_completed = ?s 
            AND updated_at < DATE_SUB(NOW(), INTERVAL ?i DAY)",
        YesNo::YES,
        'C',
        $statusShouldBeCheck,
        YesNo::NO,
        $auto_complete_days
    );

    return $affected_rows;
}

function fn_is2or_vendor_payout_get_orders_to_complete() {
    $auto_complete_days = Registry::ifGet('addons.is2or_vendor_payout.auto_complete_days', 1);

    $complete_statuses = ['C'];
    return db_get_fields(
        "SELECT order_id FROM ?:orders 
            WHERE status IN (?a) 
            AND is2or_customer_completed = ?s 
            AND updated_at < DATE_SUB(NOW(), INTERVAL ?i DAY);
        ",
        $complete_statuses,
        YesNo::NO,
        $auto_complete_days
    );
}

function fn_settings_variants_addons_is2or_vendor_payout_show_confirm_button_status() {
    return fn_is2or_vendor_payout_get_order_statuses();
}

function fn_is2or_vendor_payout_get_order_statuses() {
    // Get data status Order (O), Shipment (H), dan Return (R) sekaligus
    return db_get_hash_single_array(
        "SELECT s.status, sd.description 
         FROM ?:statuses AS s 
         LEFT JOIN ?:status_descriptions AS sd 
            ON sd.status_id = s.status_id 
         WHERE s.type IN (?a) 
            AND sd.lang_code = ?s 
         ORDER BY s.type, sd.description ASC",
        ['status', 'description'],
        [STATUSES_ORDER], // Inisial type status
        CART_LANGUAGE
    );
}

/* HOOKS FUNCTIONS */
function fn_is2or_vendor_payout_vendor_payouts_update($vendorPayout, &$data, $payout_id, $action) {
    if ($action == 'create' && $data['payout_type'] == VendorPayoutTypes::ORDER_PLACED && $data['order_id']) {
        $data['approval_status'] = VendorPayoutApprovalStatuses::PENDING;
    }
}

function fn_is2or_vendor_payout_vendor_payouts_update_pre($vendorPayout, &$data, $payout_id) {
    if ($data['payout_type'] == VendorPayoutTypes::WITHDRAWAL && !empty($_REQUEST['payment']['bank_id'])) {
        $bank_info = fn_is2or_vendor_payout_get_vendor_bank($_REQUEST['payment']['bank_id']);
        if (!empty($bank_info)) {
            $data['is2or_payout_details'] = serialize($bank_info);
        }

        unset($data['bank_id']);
    }
}
/* HOOKS FUNCTIONS */

function fn_is2or_vendor_payout_vendor_payouts_get_income(
    &$instance,
    &$params,
    &$fields,
    &$join,
    &$condition,
    &$date_condition
) {
    if ($instance->getVendor()) {
        $fields['orders_summary'] =
            'SUM(payouts.order_amount - COALESCE(orders.shipping_cost, 0)) - SUM(payouts.commission_amount)';
    }
}

function fn_is2or_vendor_payout_get_receipt($payout_id) {
    return db_get_row(
        "SELECT * FROM ?:is2or_payout_receipt WHERE payout_id = ?i",
        $payout_id
    );
}

function fn_is2or_vendor_payout_save_receipt($payout_id, $data) {
    if (empty($payout_id)) {
        return false;
    }

    $existing = db_get_field(
        "SELECT id FROM ?:is2or_payout_receipt WHERE payout_id = ?i",
        $payout_id
    );

    $now = TIME;

    $approval_status = isset($data['approval_status']) ? $data['approval_status'] : null;
    unset($data['approval_status']);

    if ($existing) {
        $data['updated_at'] = $now;
        db_query(
            "UPDATE ?:is2or_payout_receipt SET ?u WHERE payout_id = ?i",
            $data,
            $payout_id
        );
        $result = $existing;
    } else {
        $data['payout_id']  = $payout_id;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $result = db_query(
            "INSERT INTO ?:is2or_payout_receipt ?e",
            $data
        );
    }

    // Update status in tabel vendor_payouts
    if ($approval_status && $result) {
        db_query(
            "UPDATE ?:vendor_payouts SET ?u WHERE payout_id = ?i",
            ['approval_status' => $approval_status],
            $payout_id
        );
    }

    return $result;
}

function fn_is2or_vendor_payout_upload_receipt_image($file_data, $payout_id) {
    if (empty($file_data['tmp_name']) || $file_data['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file_data['type'], $allowed_types)) {
        fn_set_notification('E', __('error'), __('is2or_vendor_payout.receipt_invalid_image_type'));
        return false;
    }

    $ext      = pathinfo($file_data['name'], PATHINFO_EXTENSION);
    $filename = 'receipt_' . (int) $payout_id . '_' . TIME . '.' . strtolower($ext);

    $dir = Registry::get('config.dir.var') . 'files/payout_receipt/';
    if (!is_dir($dir)) {
        fn_mkdir($dir);
    }

    $dest = $dir . $filename;
    if (!move_uploaded_file($file_data['tmp_name'], $dest)) {
        fn_set_notification('E', __('error'), __('is2or_vendor_payout.receipt_upload_failed'));
        return false;
    }

    return 'files/payout_receipt/' . $filename;
}

function fn_is2or_vendor_payout_get_receipt_image_url($relative_path) {
    if (empty($relative_path)) {
        return '';
    }
    return Registry::get('config.current_location') . '/' .
           Registry::get('config.vendor_dir') .
           'var/' . $relative_path;
}

function fn_is2or_vendor_payout_install() {
    // 1. Cek & Tambah kolom di tabel orders
    $orders_cols = db_get_fields("SHOW COLUMNS FROM ?:orders LIKE 'is2or_customer_completed'");
    if (empty($orders_cols)) {
        db_query("ALTER TABLE ?:orders ADD `is2or_customer_completed` char(1) NOT NULL default 'N'");
    }

    // 2. Cek & Tambah kolom di tabel vendor_payouts
    $payout_cols = db_get_fields("SHOW COLUMNS FROM ?:vendor_payouts");
    
    $new_payout_columns = [
        'is2or_payout_details' => "BLOB",
        'is2or_payout_payment' => "INT(11) UNSIGNED NOT NULL DEFAULT 0",
        'is2or_payout_result'  => "BLOB"
    ];

    foreach ($new_payout_columns as $col => $definition) {
        if (!in_array($col, $payout_cols)) {
            db_query("ALTER TABLE ?:vendor_payouts ADD $col $definition");
        }
    }
}