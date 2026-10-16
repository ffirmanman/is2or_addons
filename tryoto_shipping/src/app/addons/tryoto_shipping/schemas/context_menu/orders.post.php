<?php

defined('BOOTSTRAP') or die('Access denied!');

$schema['items']['print']['items']['tryoto_print_awb'] = [
    'name'     => ['template' => 'tryoto_print_awb'],
    'dispatch' => 'orders.tryoto_print_awb',
    'data'     => [
        'action_class' => 'cm-new-window',
    ],
    'position' => 40,
];

return $schema;