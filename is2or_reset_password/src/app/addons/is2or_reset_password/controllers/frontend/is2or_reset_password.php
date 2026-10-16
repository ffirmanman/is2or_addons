<?php
/**
 * IS2OR Reset Password
 * Controller: is2or_reset_password (frontend)
 *
 */

use Tygh\Enum\NotificationSeverity;

defined('BOOTSTRAP') or die('Access denied');

// ---------------------------------------------------------------------------
// POST: submit process
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($mode === 'reset') {

        $session_data = Tygh::$app['session']['is2or_reset'] ?? [];

        if (empty($session_data['user_id']) || empty($session_data['active'])) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('text_ekey_not_valid'));
            return [CONTROLLER_STATUS_REDIRECT, fn_url('auth.recover_password')];
        }

        $user_id   = (int) $session_data['user_id'];
        $password = trim($_POST['user_data']['password1'] ?? '');
        $password2 = trim($_POST['user_data']['password2'] ?? '');

        $is_valid = true;

        if (empty($password)) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'),
                __('error_validator_required', ['[field]' => __('password')]));
            $is_valid = false;
        }

        if (empty($password2)) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'),
                __('error_validator_required', ['[field]' => __('confirm_password')]));
            $is_valid = false;
        }

        if ($is_valid && $password !== $password2) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'),
                __('error_validator_password', [
                    '[field]'  => __('confirm_password'),
                    '[field2]' => __('password'),
                ]));
            $is_valid = false;
        }

        if (!$is_valid) {
            return [CONTROLLER_STATUS_REDIRECT, fn_url('is2or_reset_password.reset')];
        }

        $auth_ref   = Tygh::$app['session']['auth'];
        $update_data = [
            'password1' => $password,
            'password2' => $password2,
        ];
        $res = fn_update_user($user_id, $update_data, $auth_ref, false, true);

        unset(Tygh::$app['session']['is2or_reset']);

        if (!empty($auth_ref['user_id'])) {
            fn_user_logout($auth_ref);
        }

        if ($res) {
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'),
                __('is2or_reset_password.password_changed_login'));
        } else {
            fn_set_notification(NotificationSeverity::ERROR, __('error'),
                __('is2or_reset_password.password_change_failed'));
        }

        return [CONTROLLER_STATUS_REDIRECT, fn_url('auth.login_form')];
    }
}

// ---------------------------------------------------------------------------
// GET: show form
// ---------------------------------------------------------------------------
if ($mode === 'reset') {

    $session_data = Tygh::$app['session']['is2or_reset'] ?? [];

    if (empty($session_data['user_id']) || empty($session_data['active'])) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('text_ekey_not_valid'));
        return [CONTROLLER_STATUS_REDIRECT, fn_url('auth.recover_password')];
    }

    Tygh::$app['view']->assign('is2or_user_id', $session_data['user_id']);
}
