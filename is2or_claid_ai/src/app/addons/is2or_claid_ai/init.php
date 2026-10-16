<?php

fn_register_hooks(
    'update_company_pre',
    'delete_product_pre',
    'delete_category_pre',
    'get_products',
    'get_product_data_post',
    'smarty_component_configurable_page_field_before_output',
    'smarty_component_configurable_page_section_before_output',
    'calculate_cart_post',
    'get_order_items_info_post',
    'allow_place_order_post',
    'change_order_status',
);