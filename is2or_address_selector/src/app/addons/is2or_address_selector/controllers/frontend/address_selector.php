<?php

use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if (empty($auth['user_id'])) {
    if (defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_DENIED];
    }

    return [CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url'))];
}

if (!isset(Tygh::$app['session']['cart']) || !is_array(Tygh::$app['session']['cart'])) {
    Tygh::$app['session']['cart'] = [];
}

$cart = &Tygh::$app['session']['cart'];

$process_profile_selection = static function ($profile_id, $return_url, $reload_checkout, $use_ajax) use ($auth, &$cart) {
    if (!fn_is2or_address_selector_is_owned_profile($auth['user_id'], $profile_id)) {
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('access_denied'));

        if ($use_ajax) {
            Tygh::$app['ajax']->assign('success', false);
        }

        return $use_ajax
            ? [CONTROLLER_STATUS_OK]
            : [CONTROLLER_STATUS_REDIRECT, 'address_selector.manage'];
    }

    [, $selectable_profiles] = fn_is2or_address_selector_get_profiles($auth);
    if (empty($selectable_profiles[$profile_id])) {
        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __('is2or_address_selector.complete_address_to_select')
        );

        if ($use_ajax) {
            Tygh::$app['ajax']->assign('success', false);
            Tygh::$app['ajax']->assign('edit_url', fn_url("address_selector.update?profile_id={$profile_id}"));
        }

        return $use_ajax
            ? [CONTROLLER_STATUS_OK]
            : [CONTROLLER_STATUS_REDIRECT, "address_selector.update?profile_id={$profile_id}"];
    }

    if (!fn_is2or_address_selector_activate_profile($auth, $cart, $profile_id)) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_occurred'));

        if ($use_ajax) {
            Tygh::$app['ajax']->assign('success', false);
        }

        return $use_ajax
            ? [CONTROLLER_STATUS_OK]
            : [CONTROLLER_STATUS_REDIRECT, $return_url];
    }

    $selected_user_data = fn_get_user_info($auth['user_id'], true, $profile_id);
    $city = !empty($selected_user_data['s_city']) ? $selected_user_data['s_city'] : $selected_user_data['b_city'];

    fn_set_notification(
        NotificationSeverity::NOTICE,
        __('notice'),
        __('is2or_address_selector.delivery_address_selected')
    );

    if ($use_ajax) {
        Tygh::$app['ajax']->assign('success', true);
        Tygh::$app['ajax']->assign('city', $city);
        Tygh::$app['ajax']->assign('profile_id', $profile_id);
        Tygh::$app['ajax']->assign('active_profile_id', $profile_id);
        Tygh::$app['ajax']->assign('reload_checkout', $reload_checkout);

        return [CONTROLLER_STATUS_OK];
    }

    return [CONTROLLER_STATUS_REDIRECT, $return_url];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update') {
        $user_data = isset($_REQUEST['user_data']) ? (array) $_REQUEST['user_data'] : [];
        $profile_id = !empty($user_data['profile_id']) ? (int) $user_data['profile_id'] : 0;
        $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'address_selector.manage';

        if ($profile_id && !fn_is2or_address_selector_is_owned_profile($auth['user_id'], $profile_id)) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('access_denied'));

            return [CONTROLLER_STATUS_REDIRECT, 'address_selector.manage'];
        }

        $profile_fields = fn_get_profile_fields();
        $ship_to_another = !empty($_REQUEST['ship_to_another']);

        fn_convert_profile_dates_to_timestamps($user_data, $profile_fields);

        if (!$ship_to_another) {
            fn_fill_address($user_data, $profile_fields);
        }

        $saved_profile_id = fn_update_user_profile($auth['user_id'], $user_data, '', $ship_to_another);

        if (!$saved_profile_id) {
            fn_save_post_data('user_data');
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_occurred'));

            $redirect_mode = $profile_id ? "address_selector.update?profile_id={$profile_id}" : 'address_selector.update';

            return [CONTROLLER_STATUS_REDIRECT, $redirect_mode];
        }

        $active_profile_id = fn_is2or_address_selector_get_active_profile_id($auth, $cart);
        if ((int) $active_profile_id === (int) $saved_profile_id) {
            fn_is2or_address_selector_activate_profile($auth, $cart, $saved_profile_id);
        }

        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('is2or_address_selector.address_saved'));

        return [CONTROLLER_STATUS_REDIRECT, $return_url];
    }

    if ($mode === 'select') {
        $profile_id = !empty($_REQUEST['profile_id']) ? (int) $_REQUEST['profile_id'] : 0;
        $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'address_selector.manage';
        $reload_checkout = !empty($_REQUEST['is_checkout_page']);
        return $process_profile_selection($profile_id, $return_url, $reload_checkout, defined('AJAX_REQUEST'));
    }
}

if ($mode === 'manage') {
    [$profiles,] = fn_is2or_address_selector_get_profiles($auth);
    $active_profile_id = fn_is2or_address_selector_get_active_profile_id($auth, $cart);

    fn_add_breadcrumb(__('my_account'), fn_url('profiles.update'));
    fn_add_breadcrumb(__('is2or_address_selector.addresses'));

    Tygh::$app['view']->assign([
        'profiles'          => $profiles,
        'active_profile_id' => $active_profile_id,
    ]);
} elseif ($mode === 'update') {
    $profile_id = !empty($_REQUEST['profile_id']) ? (int) $_REQUEST['profile_id'] : 0;
    $is_new = !$profile_id;

    if ($profile_id) {
        if (!fn_is2or_address_selector_is_owned_profile($auth['user_id'], $profile_id)) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('access_denied'));

            return [CONTROLLER_STATUS_REDIRECT, 'address_selector.manage'];
        }

        $user_data = fn_get_user_info($auth['user_id'], true, $profile_id);
    } else {
        $user_data = fn_get_user_info($auth['user_id'], false);
        $user_data['profile_id'] = 0;
        $user_data['profile_name'] = '';
    }

    $restored_user_data = fn_restore_post_data('user_data');
    if ($restored_user_data) {
        $user_data = fn_array_merge($user_data, $restored_user_data);
    }

    $profile_fields = fn_get_profile_fields();

    fn_add_breadcrumb(__('my_account'), fn_url('profiles.update'));
    fn_add_breadcrumb(__('is2or_address_selector.addresses'), fn_url('address_selector.manage'));
    fn_add_breadcrumb($is_new ? __('is2or_address_selector.add_address') : __('is2or_address_selector.edit_address'));

    Tygh::$app['view']->assign([
        'is_new'          => $is_new,
        'profile_fields'  => $profile_fields,
        'user_data'       => $user_data,
        'ship_to_another' => fn_check_shipping_billing($user_data, $profile_fields),
        'countries'       => fn_get_simple_countries(true, CART_LANGUAGE),
        'states'          => fn_get_all_states(),
    ]);
} elseif ($mode === 'delete') {
    $profile_id = !empty($_REQUEST['profile_id']) ? (int) $_REQUEST['profile_id'] : 0;
    $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'address_selector.manage';
    $active_profile_id = fn_is2or_address_selector_get_active_profile_id($auth, $cart);

    if (!fn_is2or_address_selector_is_owned_profile($auth['user_id'], $profile_id)) {
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('access_denied'));

        return [CONTROLLER_STATUS_REDIRECT, 'address_selector.manage'];
    }

    if (!fn_delete_user_profile($auth['user_id'], $profile_id)) {
        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __('is2or_address_selector.cannot_delete_primary')
        );

        return [CONTROLLER_STATUS_REDIRECT, $return_url];
    }

    if ((int) $active_profile_id === (int) $profile_id) {
        $fallback_profile_id = fn_is2or_address_selector_get_primary_profile_id($auth['user_id']);
        if ($fallback_profile_id) {
            fn_is2or_address_selector_activate_profile($auth, $cart, $fallback_profile_id);
        }
    }

    fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('is2or_address_selector.address_deleted'));

    return [CONTROLLER_STATUS_REDIRECT, $return_url];
} elseif ($mode === 'select') {
    $profile_id = !empty($_REQUEST['profile_id']) ? (int) $_REQUEST['profile_id'] : 0;
    $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'address_selector.manage';

    return $process_profile_selection($profile_id, $return_url, false, false);
}
