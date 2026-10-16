<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_is2or_cart_reminder_get_user_carts($user_id) {
    $out = [];
    $carts = db_get_array("SELECT * FROM ?:user_session_products WHERE `type`='C' and user_id=?i", $user_id);

    foreach ($carts as $cart) {
        $out[] = unserialize($cart['extra']);
    }

    return $out;
}

/* HOOKS FUNCTIONS */
function fn_is2or_cart_reminder_mailer_send_pre($mailer, $transport, &$message, $area, $lang_code) {
    if (
        Registry::get('addons.is2or_cart_reminder.cc_email')
        && Registry::get('addons.is2or_cart_reminder.cc_email_name')
        && $message->getId() == 'abandoned_cart_reminder'
    ) {
        $message->addCC(
            Registry::get('addons.is2or_cart_reminder.cc_email'),
            Registry::get('addons.is2or_cart_reminder.cc_email_name')
        );
    }
}
