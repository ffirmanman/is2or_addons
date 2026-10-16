<?php

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Admin-only guard for privileged actions
    $is_admin = !empty($auth['user_type']) && $auth['user_type'] === 'A';

    if ($mode == 'generate_address') {
        $short_address = $_REQUEST['short_address'] ?? '';

        $success = false;
        $data = '';
        $message = '';
        if ($short_address) {
            list($data, $message) = fn_is2or_spl_integration_generate_address($short_address);

            if (!empty($data)) {
                $success = true;
            }
        }

        Tygh::$app['ajax']->assign('success', $success);
        Tygh::$app['ajax']->assign('message', $message);
        Tygh::$app['ajax']->assign('data', $data);
    }

    if ($mode == 'reverify') {
        if (!$is_admin) {
            return [CONTROLLER_STATUS_DENIED];
        }

        $company_id = isset($_REQUEST['company_id']) ? (int) $_REQUEST['company_id'] : 0;

        if ($company_id) {
            $company = fn_get_company_data($company_id);
            if (!empty($company)) {
                fn_is2or_spl_integration_verify_company_address($company, $company_id);
                Tygh::$app['ajax']->assign('success', true);
            } else {
                Tygh::$app['ajax']->assign('success', false);
                Tygh::$app['ajax']->assign('message', 'Company not found');
            }
        } else {
            Tygh::$app['ajax']->assign('success', false);
            Tygh::$app['ajax']->assign('message', 'Missing company_id');
        }
    }

    if ($mode == 'override') {
        if (!$is_admin) {
            return [CONTROLLER_STATUS_DENIED];
        }

        $company_id = isset($_REQUEST['company_id']) ? (int) $_REQUEST['company_id'] : 0;

        if ($company_id && !empty($auth['user_id'])) {
            fn_is2or_spl_integration_override_verification($company_id, $auth['user_id']);
            Tygh::$app['ajax']->assign('success', true);
        } else {
            Tygh::$app['ajax']->assign('success', false);
            Tygh::$app['ajax']->assign('message', 'Missing company_id or auth');
        }
    }

    if ($mode == 'clear_override') {
        if (!$is_admin) {
            return [CONTROLLER_STATUS_DENIED];
        }

        $company_id = isset($_REQUEST['company_id']) ? (int) $_REQUEST['company_id'] : 0;

        if ($company_id) {
            fn_is2or_spl_integration_clear_override($company_id);
            Tygh::$app['ajax']->assign('success', true);
        } else {
            Tygh::$app['ajax']->assign('success', false);
            Tygh::$app['ajax']->assign('message', 'Missing company_id');
        }
    }

    return [CONTROLLER_STATUS_OK];
}