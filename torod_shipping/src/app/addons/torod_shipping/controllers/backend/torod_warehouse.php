<?php

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'populate_all_warehouses') {

    $companies = db_get_array(
        "SELECT company_id FROM ?:companies WHERE status = ?s AND (warehouse_code = ?s OR warehouse_code IS NULL)",
        'A', ''
    );

    $success_count = 0;
    $failed = array();

    foreach ($companies as $company) {
        $warehouse_code = fn_torod_generate_warehouse_code();
        $result = fn_torod_create_warehouse_address($company['company_id'], $warehouse_code);

        if ($result['success']) {
            $success_count++;
        } else {
            $failed[] = array(
                'company_id' => $company['company_id'],
                'error' => $result['error'],
            );
        }
    }

    $failed_count = count($failed);

    fn_set_notification('N', __('notice'),
        "Populated {$success_count} warehouse codes. {$failed_count} failed.");

    return array(CONTROLLER_STATUS_OK, 'companies.manage');
}
