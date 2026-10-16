<?php

defined('BOOTSTRAP') or die('Access denied');

$schema['is2or_notification_user'] = [
    'content' => [
        'notification_user_data' => [
            'type'     => 'function',
            'function' => ['fn_is2or_notification_user_get_block_data'],
        ],
    ],
    'settings' => [],
    'templates' => [
        'addons/is2or_notification_user/blocks/notification_settings.tpl' => [
            'name' => __('is2or_notification_user.template_name'),
        ],
    ],
    'wrappers' => 'blocks/wrappers',
    'cache'    => false,
];

return $schema;
