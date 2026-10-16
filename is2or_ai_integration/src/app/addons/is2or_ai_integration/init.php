<?php

defined('BOOTSTRAP') or die('Access denied');

// get_products_pre  : intercept the search request, ask the AI service, inject pid filter
// get_products_post : re-order the fetched $products array so it matches the AI ranking
//                      (a SQL "IN (...)" filter does NOT preserve array order, so we must
//                      re-sort in PHP after CS-Cart has fetched the rows)
fn_register_hooks(
    'update_product_post',
    'delete_product_post',
    'get_products_pre',
    'get_products_post',
    'get_product_data_post',
    'update_company'
);
