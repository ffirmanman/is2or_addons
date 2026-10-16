<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Registry;
use Tygh\Tygh;

if ($mode == 'cron') {
    $date_format = Registry::get('settings.Appearance.calendar_date_format') == 'month_first'
        ? 'm/d/Y'
        : 'd/m/Y';

    $carts_list = [];
    $default_params = [
        'product_type_c' => true,
        'period' => 'C',
    ];

    list($carts_list['two_weeks_ago']) = fn_get_carts(array_merge($default_params, [
        'time_from' => date($date_format, strtotime('- 14days')),
        'time_to' => date($date_format, strtotime('- 14day'))
    ]));

    list($carts_list['a_week_ago']) = fn_get_carts(array_merge($default_params, [
        'time_from' => date($date_format, strtotime('- 7days')),
        'time_to' => date($date_format, strtotime('- 7day'))
    ]));

    list($carts_list['yesterday']) = fn_get_carts(array_merge($default_params, [
        'time_from' => date($date_format, strtotime('- 1days')),
        'time_to' => date($date_format, strtotime('- 1day'))
    ]));

    $event_dispatcher = Tygh::$app['event.dispatcher'];
    $force_notification = [
        UserTypes::CUSTOMER => true
    ];
    $notification_rules = Tygh::$app['event.notification_settings.factory']->create($force_notification);

    $currencies = fn_get_currencies();
    $currency_data = $currencies[CART_PRIMARY_CURRENCY];

    $notified_user_ids = [];
    $notified_emails = [];
    foreach ($carts_list as $type => $carts) {
        foreach ($carts as $cart) {
            if (in_array($cart['user_id'], $notified_user_ids) || empty($cart['user_data'])) {
                continue;
            }
            
            $notification_data = [
                'email' => $cart['user_data']['email'],
                'carts' => fn_is2or_cart_reminder_get_user_carts($cart['user_id']),
                'currency_data' => $currency_data,
                'url' => fn_url('checkout.cart', SiteArea::STOREFRONT)
            ];

            $event_dispatcher->dispatch('cart_reminder.abandoned_cart_reminder', $notification_data, $notification_rules);
            $notified_user_ids[] = $cart['user_id'];
            $notified_emails[] = $cart['user_data']['email'];

            fn_log_event('general', 'runtime', [
                'message' => 'Abandoned cart reminder mail sent to ' . $notification_data['email']
            ]);
        }
    }

    fn_print_die($notified_emails);
}