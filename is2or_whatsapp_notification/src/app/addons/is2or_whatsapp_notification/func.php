<?php
function fn_is2or_whatsapp_notification_notification_settings_get_transports_post(&$transports) {
    $transports['whatsapp'] = [
        'permissions' => 'manage_notifications',
        'icon'        => 'icon-phone', // Gunakan icon yang sesuai
    ];
}
