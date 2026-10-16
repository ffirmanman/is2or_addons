<?php

defined('BOOTSTRAP') or die('Access denied');

fn_register_hooks(
    'update_company_post',
    'update_user_pre',
    'update_user_profile_pre',
    'update_user_profile_post',
    'tryoto_create_shipment_data',
    'tryoto_create_shipment_sender_data',
    'torod_create_order_data',
    'torod_create_shipment_sender_data',
    'index_scripts_post',
    'get_companies_list',
    'get_company_data_post'
);

