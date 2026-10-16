<?php

defined('BOOTSTRAP') or die('Access denied!');

$schema['items']['print']['items']['torod_print_awb'] = [
    'name'     => ['template' => 'torod_print_awb'],
    'dispatch' => 'orders.torod_print_awb',
    'data'     => [
        'action_class' => 'cm-new-window',
    ],
    'position' => 40,
];

return $schema;
