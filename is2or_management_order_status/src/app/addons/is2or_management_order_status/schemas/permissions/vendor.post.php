<?php
defined('BOOTSTRAP') or die('Access denied');

/**
 * Memberikan izin akses penuh kepada Vendor
 * untuk controller is2or_order_management
 */
$schema['controllers']['is2or_order_management'] = [
    'permissions' => true,
    'modes' => [
        'manage' => [
            'permissions' => true
        ]
    ]
];

return $schema;
