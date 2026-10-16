<?php

defined('BOOTSTRAP') or die('Access denied');

$schema['is2or_login_activity'] = [
    'content' => [
        'login_activity_data' => [
            'type'     => 'function',
            'function' => ['fn_is2or_login_activity_get_block_data'],
        ],
    ],
    'settings' => [],
    'templates' => [
        'addons/is2or_login_activity/blocks/login_activity.tpl' => [
            'name' => __('is2or_login_activity.template_name'),
        ],
    ],
    'wrappers' => 'blocks/wrappers',
    'cache'    => false,
];

return $schema;
