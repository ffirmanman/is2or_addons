<?php
defined('BOOTSTRAP') or die('Access denied');

/**
 * Menambahkan menu ke dalam kategori 'Orders' di sidebar (central)
 */
$schema['central']['orders']['items']['view_orders'] = [
    'href' => 'is2or_order_management.manage',
    'position' => 10, // Menggunakan angka kecil agar muncul paling atas
    'title' => __('orders'), // Gunakan variabel bahasa
];

return $schema;
