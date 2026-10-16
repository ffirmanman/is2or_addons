<?php
defined('BOOTSTRAP') or die('Access denied');

// Memberikan izin kepada vendor untuk mengakses controller baru
$schema['controllers']['is2or_order_management'] = [
    'permissions' => true,
];

return $schema;
