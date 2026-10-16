<?php

defined('BOOTSTRAP') or die('Access denied');

$auth    = Tygh::$app['session']['auth'] ?? [];
$user_id = (int) ($auth['user_id'] ?? 0);

if ($user_id <= 0) {
    fn_set_notification('E', __('error'), __('is2or_login_activity.not_logged_in'));
    return [CONTROLLER_STATUS_REDIRECT, fn_url('')];
}

if ($mode === 'index') {

    fn_add_breadcrumb(__('is2or_login_activity.title'));
    Tygh::$app['view']->assign('login_activity_data', fn_is2or_login_activity_get_block_data());

} elseif ($mode === 'logout_session') {

    $csrf_token = $_REQUEST['csrf_token'] ?? '';
    if (empty($csrf_token) || $csrf_token !== (Tygh::$app['session']['security_hash'] ?? '')) {
        fn_set_notification('E', __('error'), __('is2or_login_activity.session_remove_failed'));
        return [CONTROLLER_STATUS_REDIRECT, fn_url('is2or_login_activity.index')];
    }

    $target_id = fn_is2or_login_activity_sanitize_id($_REQUEST['id'] ?? '');
    $result    = fn_is2or_login_activity_logout_session($target_id, $user_id);

    if ($result) {
        fn_set_notification('N', __('notice'), __('is2or_login_activity.session_removed'));
    } else {
        fn_set_notification('E', __('error'), __('is2or_login_activity.session_remove_failed'));
    }

    return [CONTROLLER_STATUS_REDIRECT, fn_url('is2or_login_activity.index')];

} elseif ($mode === 'logout_all') {

    $csrf_token = $_REQUEST['csrf_token'] ?? '';
    if (empty($csrf_token) || $csrf_token !== (Tygh::$app['session']['security_hash'] ?? '')) {
        fn_set_notification('E', __('error'), __('is2or_login_activity.session_remove_failed'));
        return [CONTROLLER_STATUS_REDIRECT, fn_url('is2or_login_activity.index')];
    }

    $count = fn_is2or_login_activity_logout_all_sessions($user_id);
    fn_set_notification('N', __('notice'), __('is2or_login_activity.all_sessions_removed', ['[count]' => $count]));

    return [CONTROLLER_STATUS_REDIRECT, fn_url('is2or_login_activity.index')];
}