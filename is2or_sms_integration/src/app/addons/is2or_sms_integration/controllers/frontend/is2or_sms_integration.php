<?php

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Registry;

if ($mode == 'otp_verify') {
    if (empty($_SESSION['is2or_sms_otp_pending']['user_id'])) {
        return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form');
    }

    $ttl_minutes = (int) Registry::get('addons.is2or_sms_integration.login_otp_ttl_minutes');
    if ($ttl_minutes <= 0) {
        $ttl_minutes = 5;
    }

    Tygh\Registry::get('view')->assign('otp_ttl_minutes', $ttl_minutes);
}

if ($mode == 'otp_verify_submit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_SESSION['is2or_sms_otp_pending']['user_id'])) {
        return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form');
    }

    $user_id = (int) $_SESSION['is2or_sms_otp_pending']['user_id'];
    $code    = !empty($_REQUEST['otp_code']) ? trim($_REQUEST['otp_code']) : '';

    if ($code !== '' && fn_is2or_sms_integration_verify_login_otp($user_id, $code)) {
        fn_is2or_sms_integration_complete_pending_login();
        fn_set_notification('N', __('notice'), __('is2or_sms_integration.otp_verified'));

        return array(CONTROLLER_STATUS_REDIRECT, 'index.update');
    }

    fn_set_notification('E', __('error'), __('is2or_sms_integration.otp_invalid'));

    return array(CONTROLLER_STATUS_REDIRECT, 'is2or_sms_integration.otp_verify');
}

if ($mode == 'otp_resend' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_SESSION['is2or_sms_otp_pending']['user_id'])) {
        return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form');
    }

    $user_id = (int) $_SESSION['is2or_sms_otp_pending']['user_id'];
    $phone   = fn_is2or_sms_integration_get_user_phone($user_id);

    if ($phone !== '') {
        $sent = fn_is2or_sms_integration_send_login_otp($user_id, $phone);

        if ($sent) {
            fn_set_notification('N', __('notice'), __('is2or_sms_integration.otp_resent'));
        } else {
            fn_set_notification('E', __('error'), __('is2or_sms_integration.otp_resend_failed'));
        }
    } else {
        fn_set_notification('E', __('error'), __('is2or_sms_integration.otp_no_phone'));
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'is2or_sms_integration.otp_verify');
}

if ($mode == 'otp_cancel' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    fn_is2or_sms_integration_cancel_pending_login();

    return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form');
}