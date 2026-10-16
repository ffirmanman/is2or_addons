<?php

$schema['controllers']['is2or_channel_integrations'] = [
    'permissions' => true,
    'allowed_actions' => [
        'manage',
        'request_authorize_url',
        'callback_result',
        'trigger_import',
    ],
];

return $schema;