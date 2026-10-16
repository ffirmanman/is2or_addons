<?php

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'customer_geolocation' && !empty($auth['user_id'])) {
    $cart = !empty(Tygh::$app['session']['cart']) && is_array(Tygh::$app['session']['cart'])
        ? Tygh::$app['session']['cart']
        : [];

    [$profiles,] = fn_is2or_address_selector_get_profiles($auth);

    Tygh::$app['view']->assign([
        'address_selector_profiles'           => $profiles,
        'address_selector_active_profile_id'  => fn_is2or_address_selector_get_active_profile_id($auth, $cart),
    ]);
}
