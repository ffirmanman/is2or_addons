<?php

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

fn_register_hooks(
//    'get_shipping_info_post',
    'delete_shipments',
//    'create_shipment',
    'create_shipment_post',
    'pre_place_order',
    'delete_shipments_pre',
    'clear_cache_post',
    'calculate_cart_taxes_pre'
);
