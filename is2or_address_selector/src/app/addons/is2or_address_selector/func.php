<?php

use Tygh\Enum\ProfileFieldSections;
use Tygh\Registry;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

function fn_is2or_address_selector_get_primary_profile_id($user_id)
{
    if (empty($user_id)) {
        return 0;
    }

    $profile_id = (int) db_get_field(
        "SELECT profile_id FROM ?:user_profiles WHERE user_id = ?i AND profile_type = 'P'",
        $user_id
    );

    if ($profile_id) {
        return $profile_id;
    }

    return (int) db_get_field(
        'SELECT profile_id FROM ?:user_profiles WHERE user_id = ?i ORDER BY profile_id ASC LIMIT 1',
        $user_id
    );
}

function fn_is2or_address_selector_is_owned_profile($user_id, $profile_id)
{
    if (empty($user_id) || empty($profile_id)) {
        return false;
    }

    return (bool) db_get_field(
        'SELECT profile_id FROM ?:user_profiles WHERE user_id = ?i AND profile_id = ?i',
        $user_id,
        $profile_id
    );
}

function fn_is2or_address_selector_get_profiles(array $auth)
{
    if (empty($auth['user_id'])) {
        return [[], []];
    }

    [$profiles, $selectable_profiles] = fn_checkout_get_user_profiles($auth);

    foreach ($profiles as $profile_id => &$profile) {
        $profile['is_primary'] = $profile['profile_type'] === 'P';
        $profile['is_selectable'] = !empty($selectable_profiles[$profile_id]);
        $profile['display_city'] = !empty($profile['s_city']) ? $profile['s_city'] : $profile['b_city'];
        $profile['display_country'] = !empty($profile['s_country_descr']) ? $profile['s_country_descr'] : $profile['b_country_descr'];
        $profile['display_state'] = !empty($profile['s_state_descr']) ? $profile['s_state_descr'] : $profile['b_state_descr'];
    }
    unset($profile);

    return [$profiles, $selectable_profiles];
}

function fn_is2or_address_selector_get_active_profile_id(array $auth, array $cart = [])
{
    if (empty($auth['user_id'])) {
        return 0;
    }

    $profiles = fn_get_user_profiles($auth['user_id']);
    if (!$profiles) {
        return 0;
    }

    $candidate_ids = [
        isset($cart['user_data']['profile_id']) ? (int) $cart['user_data']['profile_id'] : 0,
        isset($cart['profile_id']) ? (int) $cart['profile_id'] : 0,
        (int) db_get_field('SELECT active_address_profile_id FROM ?:users WHERE user_id = ?i', $auth['user_id']),
        (int) fn_get_session_data('last_order_profile_id'),
    ];

    foreach ($candidate_ids as $candidate_id) {
        if ($candidate_id && isset($profiles[$candidate_id])) {
            return $candidate_id;
        }
    }

    return fn_is2or_address_selector_get_primary_profile_id($auth['user_id']);
}

function fn_is2or_address_selector_activate_profile(array $auth, array &$cart, $profile_id, $is_detected = true)
{
    if (empty($auth['user_id']) || !fn_is2or_address_selector_is_owned_profile($auth['user_id'], $profile_id)) {
        return false;
    }

    $profile_id = (int) $profile_id;

    db_query(
        'UPDATE ?:users SET active_address_profile_id = ?i WHERE user_id = ?i',
        $profile_id,
        $auth['user_id']
    );

    if (!isset($cart['user_data']) || !is_array($cart['user_data'])) {
        $cart['user_data'] = [];
    }
    $cart['profile_id'] = $profile_id;
    $cart['profile_changed'] = true;
    $cart['calculate_shipping'] = true;
    $cart['recalculate'] = true;
    $cart['user_data']['profile_id'] = $profile_id;

    fn_set_session_data('last_order_profile_id', $profile_id);

    $location_manager = Tygh::$app['location'];
    if ($is_detected) {
        $location_manager->setIsDetected(true);
    }
    $location_manager->setLocationFromUserProfile($profile_id);

    $selected_user_data = fn_get_user_info($auth['user_id'], true, $profile_id);
    if ($selected_user_data) {
        $cart['user_data'] = fn_array_merge($cart['user_data'], $selected_user_data);
    }

    fn_save_cart_content($cart, $auth['user_id']);

    return true;
}

function fn_is2or_address_selector_get_profile_fields_post($location, $_auth, $lang_code, $params, &$profile_fields, $sections)
{
    if (
        AREA !== 'C'
        || $location !== 'C'
        || Registry::get('runtime.controller') !== 'profiles'
        || Registry::get('runtime.mode') !== 'update'
    ) {
        return;
    }

    unset(
        $profile_fields[ProfileFieldSections::BILLING_ADDRESS],
        $profile_fields[ProfileFieldSections::SHIPPING_ADDRESS]
    );
}

function fn_is2or_address_selector_update_profile($action, $user_data, $current_user_data) {

    $is_main_profile = ($user_data['profile_name'] == 'Main' || $user_data['profile_name'] == '');
    if ($is_main_profile) {
        $user_data_new = db_get_row("SELECT firstname, lastname, phone FROM ?:users WHERE user_id = ?i", $user_data['user_id']);

        if (!empty($user_data_new)) {
            db_query(
                "UPDATE ?:user_profiles SET b_phone = ?s, s_phone = ?s, b_firstname = ?s, s_firstname = ?s, b_lastname = ?s, s_lastname = ?s WHERE profile_id = ?i",
                $user_data_new['phone'],
                $user_data_new['phone'],
                $user_data_new['firstname'],
                $user_data_new['firstname'],
                $user_data_new['lastname'],
                $user_data_new['lastname'],
                $user_data['profile_id']
            );
        }
    }
}