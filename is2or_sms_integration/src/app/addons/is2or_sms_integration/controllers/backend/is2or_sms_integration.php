<?php

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Registry;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'test_send') {
        $phone   = !empty($_REQUEST['test_phone']) ? trim($_REQUEST['test_phone']) : '';
        $message = !empty($_REQUEST['test_message']) ? trim($_REQUEST['test_message']) : '';

        if ($phone === '' || $message === '') {
            fn_set_notification('E', __('error'), __('is2or_sms_integration.phone_and_message_required'));
        } else {
            $result = fn_is2or_sms_integration_send_sms($phone, $message);

            if ($result) {
                fn_set_notification('N', __('notice'), __('is2or_sms_integration.sms_sent_successfully'));
            } else {
                fn_set_notification('E', __('error'), __('is2or_sms_integration.sms_failed_to_send'));
            }
        }

        return array(CONTROLLER_STATUS_REDIRECT, 'is2or_sms_integration.manage');
    }
}

if ($mode == 'manage') {
    $params = $_REQUEST;

    list($logs, $search) = fn_is2or_sms_integration_get_logs(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page')
    );

    Tygh\Registry::get('view')->assign('logs', $logs);
    Tygh\Registry::get('view')->assign('search', $search);
}
