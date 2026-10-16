<?php
defined('BOOTSTRAP') or die('Access denied');

/**
 * Mengganti URL "View Orders" di panel Vendor
 * agar mengarah ke halaman Order Management kustom.
 */
if (isset($schema['central']['orders']['items']['view_orders'])) {
    $schema['central']['orders']['items']['view_orders']['href'] = 'is2or_order_management.manage';
}

return $schema;
