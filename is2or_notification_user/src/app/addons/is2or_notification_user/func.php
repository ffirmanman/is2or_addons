<?php

defined('BOOTSTRAP') or die('Access denied');

/**
 * Notification type definitions.
 *
 * Each type has:
 *   - group    : translation key for the section header (e.g. "Transaksi Pembelian")
 *   - label    : translation key for the row label
 *   - channels : which channels are supported (currently only 'email')
 *   - default  : default enabled value (1 = on, 0 = off)
 *
 * CS-Cart order-status notifications map to the keys below.
 * The `type` values must be kept stable — they are stored in the DB.
 */
function fn_is2or_notification_user_get_types()
{
    return [
        // ── Transaksi Pembelian ─────────────────────────────────────
        'order_placed' => [
            'group'    => 'is2or_notification_user.group_purchase',
            'label'    => 'is2or_notification_user.type_order_placed',
            'channels' => ['email'],
            'default'  => 1,
        ],
        'order_paid' => [
            'group'    => 'is2or_notification_user.group_purchase',
            'label'    => 'is2or_notification_user.type_order_paid',
            'channels' => ['email'],
            'default'  => 1,
        ],
        'order_processing' => [
            'group'    => 'is2or_notification_user.group_purchase',
            'label'    => 'is2or_notification_user.type_order_processing',
            'channels' => ['email'],
            'default'  => 1,
        ],
        'order_shipped' => [
            'group'    => 'is2or_notification_user.group_purchase',
            'label'    => 'is2or_notification_user.type_order_shipped',
            'channels' => ['email'],
            'default'  => 1,
        ],
        'order_completed' => [
            'group'    => 'is2or_notification_user.group_purchase',
            'label'    => 'is2or_notification_user.type_order_completed',
            'channels' => ['email'],
            'default'  => 1,
        ],
        'order_reminder' => [
            'group'    => 'is2or_notification_user.group_purchase',
            'label'    => 'is2or_notification_user.type_order_reminder',
            'channels' => ['email'],
            'default'  => 1,
        ],
        // ── Promo ───────────────────────────────────────────────────
        'newsletter' => [
            'group'    => 'is2or_notification_user.group_promo',
            'label'    => 'is2or_notification_user.type_newsletter',
            'channels' => ['email'],
            'default'  => 1,
        ],
        'promo_coupon' => [
            'group'    => 'is2or_notification_user.group_promo',
            'label'    => 'is2or_notification_user.type_promo_coupon',
            'channels' => ['email'],
            'default'  => 1,
        ],
        // ── Akun ────────────────────────────────────────────────────
        'account_password' => [
            'group'    => 'is2or_notification_user.group_account',
            'label'    => 'is2or_notification_user.type_account_password',
            'channels' => ['email'],
            'default'  => 1,
        ],
        'account_profile' => [
            'group'    => 'is2or_notification_user.group_account',
            'label'    => 'is2or_notification_user.type_account_profile',
            'channels' => ['email'],
            'default'  => 1,
        ],
    ];
}

/**
 * Load the current preferences for a user.
 * Returns a flat array keyed as "type:channel" => 1|0.
 * Missing rows are treated as the type's default value.
 *
 * @param int $user_id
 * @return array
 */
function fn_is2or_notification_user_get_prefs($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return [];
    }

    $types = fn_is2or_notification_user_get_types();
    $rows  = db_get_array(
        'SELECT `type`, `channel`, `enabled`
         FROM ?:is2or_notification_user
         WHERE `user_id` = ?i',
        $user_id
    );

    // Index DB rows
    $db_map = [];
    foreach ($rows as $row) {
        $db_map[$row['type'] . ':' . $row['channel']] = (int) $row['enabled'];
    }

    // Merge with defaults
    $prefs = [];
    foreach ($types as $type_key => $type_def) {
        foreach ($type_def['channels'] as $channel) {
            $key          = $type_key . ':' . $channel;
            $prefs[$key]  = isset($db_map[$key]) ? $db_map[$key] : $type_def['default'];
        }
    }

    return $prefs;
}

/**
 * Save user preferences from POSTed form data.
 * The form sends checkboxes only when checked; unchecked = absent.
 *
 * @param int   $user_id
 * @param array $post_data   Raw $_POST array (or subset)
 * @return bool
 */
function fn_is2or_notification_user_save_prefs($user_id, array $post_data)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return false;
    }

    $types = fn_is2or_notification_user_get_types();

    // Delete old rows and re-insert — simplest & safest approach
    db_query('DELETE FROM ?:is2or_notification_user WHERE user_id = ?i', $user_id);

    $insert_rows = [];
    foreach ($types as $type_key => $type_def) {
        foreach ($type_def['channels'] as $channel) {
            $field_name = 'is2or_notif_' . $type_key . '_' . $channel;
            $enabled    = isset($post_data[$field_name]) ? 1 : 0;

            $insert_rows[] = [
                'user_id' => $user_id,
                'type'    => $type_key,
                'channel' => $channel,
                'enabled' => $enabled,
            ];
        }
    }

    if (!empty($insert_rows)) {
        db_query('INSERT INTO ?:is2or_notification_user ?m', $insert_rows);
    }

    return true;
}

/**
 * Check if a specific notification type+channel is enabled for a user.
 * Used by the send_mail hook or other places to gate sending.
 *
 * @param int    $user_id
 * @param string $type
 * @param string $channel
 * @return bool
 */
function fn_is2or_notification_user_is_enabled($user_id, $type, $channel = 'email')
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return true; // guests: always send
    }

    $types = fn_is2or_notification_user_get_types();
    if (!isset($types[$type])) {
        return true; // unknown type: don't block
    }

    $row = db_get_field(
        'SELECT `enabled`
         FROM ?:is2or_notification_user
         WHERE user_id = ?i AND `type` = ?s AND `channel` = ?s',
        $user_id,
        $type,
        $channel
    );

    // If no row yet, use the type's default
    if ($row === null) {
        return (bool) $types[$type]['default'];
    }

    return (bool) $row;
}


/**
 * Block content getter — called by the block manager.
 *
 * @return array
 */
function fn_is2or_notification_user_get_block_data($value = '', array $block = [], array $block_scheme = [])
{
    $auth    = Tygh::$app['session']['auth'] ?? [];
    $user_id = (int) ($auth['user_id'] ?? 0);

    if ($user_id <= 0) {
        return ['user_id' => 0, 'types' => [], 'prefs' => [], 'groups' => []];
    }

    $types = fn_is2or_notification_user_get_types();
    $prefs = fn_is2or_notification_user_get_prefs($user_id);

    // Build grouped structure for the template
    $groups = [];
    foreach ($types as $type_key => $type_def) {
        $group_key = $type_def['group'];
        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [];
        }
        $groups[$group_key][$type_key] = $type_def;
    }

    return [
        'user_id' => $user_id,
        'types'   => $types,
        'prefs'   => $prefs,
        'groups'  => $groups,
    ];
}

/**
 * Hook: phpmailertransport_send_message_before_send
 *
 * Fires inside PhpMailerTransport::sendMessage() just before PHPMailer->send().
 * Signature (from CS-Cart 4.19 source):
 *   fn_set_hook('phpmailertransport_send_message_before_send', $this, $message);
 *
 * $transport  — \Tygh\Mailer\Transports\PhpMailerTransport (extends PHPMailer)
 * $message    — \Tygh\Mailer\Message
 *
 * To block sending we call $transport->clearAllRecipients() so PHPMailer
 * has nobody to send to and the inner send() call will fail silently.
 *
 * @param \Tygh\Mailer\Transports\PhpMailerTransport $transport
 * @param \Tygh\Mailer\Message                       $message
 * @return void
 */
function fn_is2or_notification_user_phpmailertransport_send_message_before_send(&$transport, &$message)
{
    // Resolve the notification type from the Message object
    $type = fn_is2or_notification_user_resolve_type_from_message($message);
    if ($type === null) {
        return; // unmapped — don't block
    }

    // Try to get user_id from the Message extra data, then fall back to session
    $user_id = 0;

    // CS-Cart stores vars passed to the mail template inside the Message
    if (method_exists($message, 'getVars')) {
        $vars    = $message->getVars();
        $user_id = (int) ($vars['user_id'] ?? $vars['order']['user_id'] ?? 0);
    }

    // Last resort: current logged-in user (covers profile-page flows)
    if ($user_id <= 0) {
        $auth    = Tygh::$app['session']['auth'] ?? [];
        $user_id = (int) ($auth['user_id'] ?? 0);
    }

    if ($user_id <= 0) {
        return; // guest — never block
    }

    if (!fn_is2or_notification_user_is_enabled($user_id, $type, 'email')) {
        // Remove all recipients so PHPMailer skips the actual send
        $transport->clearAllRecipients();
    }
}

/**
 * Resolve notification type from a \Tygh\Mailer\Message object.
 *
 * The Message carries the template path used to render the email body.
 * We match on that path (same map as before, but sourced from the object).
 *
 * @param  \Tygh\Mailer\Message $message
 * @return string|null  notification type key or null = don't intercept
 */
function fn_is2or_notification_user_resolve_type_from_message($message)
{
    $template = '';
    if (method_exists($message, 'getTemplate')) {
        $template = (string) $message->getTemplate();
    }

    $template_map = [
        'mail/orders/order_notification.tpl'  => 'order_placed',
        'mail/orders/order_placed.tpl'        => 'order_placed',
        'mail/orders/order_paid.tpl'          => 'order_paid',
        'mail/orders/order_processing.tpl'    => 'order_processing',
        'mail/orders/order_shipped.tpl'       => 'order_shipped',
        'mail/orders/order_completed.tpl'     => 'order_completed',
        'mail/orders/order_reminder.tpl'      => 'order_reminder',
        'mail/newsletters/newsletter.tpl'     => 'newsletter',
        'mail/newsletters/unsubscribe.tpl'    => 'newsletter',
        'mail/profiles/change_password.tpl'   => 'account_password',
        'mail/profiles/profile_updated.tpl'   => 'account_profile',
    ];

    // Exact match first
    if (isset($template_map[$template])) {
        return $template_map[$template];
    }

    // Partial match (template path may be prefixed by theme dir)
    foreach ($template_map as $pattern => $type_key) {
        if (str_contains($template, $pattern)) {
            return $type_key;
        }
    }

    return null;
}
