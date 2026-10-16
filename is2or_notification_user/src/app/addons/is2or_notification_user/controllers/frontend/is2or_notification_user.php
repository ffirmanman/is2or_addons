<?php

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'index') {
    $auth    = Tygh::$app['session']['auth'] ?? [];
    $user_id = (int) ($auth['user_id'] ?? 0);
    
    fn_add_breadcrumb(__('is2or_notification_user.title'));

    if ($user_id > 0) {
        Tygh::$app['view']->assign('notification_user_data', fn_is2or_notification_user_get_block_data());
    } else {
        fn_set_notification('E', __('error'), __('is2or_notification_user.not_logged_in'));
        fn_redirect(fn_url(''));
        exit;
    }

} elseif ($mode === 'save') {
    $auth    = Tygh::$app['session']['auth'] ?? [];
    $user_id = (int) ($auth['user_id'] ?? 0);

    if ($user_id <= 0) {
        fn_set_notification('E', __('error'), __('is2or_notification_user.not_logged_in'));
        fn_redirect(fn_url(''));
        exit;
    }

    fn_is2or_notification_user_save_prefs($user_id, $_POST);

    fn_set_notification('N', __('notice'), __('is2or_notification_user.saved'));

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($referer)) {
        fn_redirect($referer);
    } else {
        fn_redirect(fn_url('profiles.update'));
    }
}
