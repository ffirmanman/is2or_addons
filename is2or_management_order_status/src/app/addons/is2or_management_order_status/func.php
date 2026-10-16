<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;

function fn_is2or_management_order_status_change_order_status_pre($order_id, $status_to, $status_from, $force_notification, $place_order, $order_info, &$allow_status_update) {

    // 1. Abaikan jika status tidak berubah atau sedang dalam proses pembuatan order baru
    if (empty($status_from) || $status_from === $status_to || $place_order) {
        return;
    }

    if ($status_from === 'N') {
        return;
    }

    // 2. Ambil setting transisi
    $transitions_raw = Registry::get('addons.is2or_management_order_status.status_transitions');
    $allowed_transitions = fn_is2or_parse_status_transitions($transitions_raw);

    // Jika setting kosong, izinkan semua
    if (empty($allowed_transitions)) {
        return;
    }

    // 3. Validasi Transisi
    $is_allowed = false;
    if (isset($allowed_transitions[$status_from]) && in_array($status_to, $allowed_transitions[$status_from])) {
        $is_allowed = true;
    }

    if (!$is_allowed) {
        // Hentikan update status
        $allow_status_update = false;

        // 4. Cegah spam notifikasi yang sama saat bulk update
        // Kita gunakan static variable untuk memastikan pesan error yang sama hanya muncul sekali per request
        static $last_error_msg = '';

        $all_statuses = fn_get_statuses(STATUSES_ORDER, [], true, false, CART_LANGUAGE);
        $from_name = isset($all_statuses[$status_from]) ? $all_statuses[$status_from]['description'] : $status_from;
        $to_name = isset($all_statuses[$status_to]) ? $all_statuses[$status_to]['description'] : $status_to;

        $current_error_msg = $from_name . $to_name;

        if ($last_error_msg !== $current_error_msg) {
            fn_set_notification('E', __('error'), __("is2or_error_invalid_transition", [
                '[from]' => $from_name,
                '[to]'   => $to_name
            ]));
            $last_error_msg = $current_error_msg;
        }
    }
}

/**
 * Helper: Mengubah string Text Area menjadi array
 * Input: "O:I,D\nI:P" -> Output: ['O' => ['I', 'D'], 'I' => ['P']]
 */
function fn_is2or_parse_status_transitions($raw_string) {
    if (empty($raw_string)) {
        return [];
    }

    $rules = [];
    $lines = explode("\n", str_replace("\r", "", $raw_string));

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $parts = explode(':', $line);
        if (count($parts) == 2) {
            $from = trim($parts[0]);
            $to_list = explode(',', trim($parts[1]));
            $rules[$from] = array_map('trim', $to_list);
        }
    }

    return $rules;
}

/**
 * Memastikan filter status bekerja saat parameter status ada di URL
 */
/**
 * Memastikan filter status bekerja saat parameter status ada di URL
 */
function fn_is2or_management_order_status_get_orders($params, &$fields, &$sortings, &$condition, &$join, &$group_key) {

}

function fn_settings_variants_addons_is2or_management_order_status_tab_paid_statuses() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_tab_packing_statuses() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_tab_shipping_statuses() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_tab_delivered_statuses() {
    return fn_is2or_management_order_status_get_order_statuses();
}
function fn_settings_variants_addons_is2or_management_order_status_tab_complete_statuses() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_tab_declined_statuses() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_tab_return_statuses() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_tab_cancelled_statuses() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_packing_status() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_declined_status() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_shipping_status() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_delivered_status() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_complete_status() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_cancelled_status() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_settings_variants_addons_is2or_management_order_status_paid_status() {
    return fn_is2or_management_order_status_get_order_statuses();
}

function fn_is2or_management_order_status_get_order_statuses() {
    // Ambil data status Order (O), Shipment (H), dan Return (R) sekaligus
    return db_get_hash_single_array(
        "SELECT s.status, sd.description 
         FROM ?:statuses AS s 
         LEFT JOIN ?:status_descriptions AS sd 
            ON sd.status_id = s.status_id 
         WHERE s.type IN (?a) 
            AND sd.lang_code = ?s 
         ORDER BY s.type, sd.description ASC",
        ['status', 'description'],
        [STATUSES_ORDER], // Inisial tipe status
        CART_LANGUAGE
    );
}

function fn_is2or_management_order_status_get_order_short_info($order_id)
{
    if (!empty($order_id)) {
        return db_get_row(
            'SELECT total, status, issuer_id, firstname, lastname, timestamp, is_parent_order, storefront_id, company_id, user_id FROM ?:orders WHERE order_id = ?i',
            $order_id
        );
    }

    return false;
}
