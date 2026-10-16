<?php

function fn_blog_categories_get_categories($params = [], $items_per_page = 0) {
    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page
    ];
    $params = array_merge($default_params, $params);

    $sortings = array(
        'category_id' => '?:blog_categories.category_id',
        'category' => '?:blog_categories.category',
    );

    $sorting = db_sort($params, $sortings, 'category_id', 'asc');

    $condition = $limit = $join = '';

    $fields = [
        '?:blog_categories.category_id',
        '?:blog_categories.category'
    ];

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:blog_categories $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $data = db_get_hash_array(
        "SELECT ?p FROM ?:blog_categories " .
        $join .
        " WHERE 1 ?p ?p ?p",
        'category_id', implode(', ', $fields), $condition, $sorting, $limit
    );

    return [$data, $params];
}

function fn_blog_categories_update_category($id, $data) {
    if ($id) {
        db_query("UPDATE ?:blog_categories SET ?u WHERE category_id=?i", $data, $id);
    } else {
        $id = db_query("INSERT INTO ?:blog_categories ?e", $data);
    }

    return $id;
}

function fn_blog_categories_delete_category($id) {
    db_query("DELETE FROM ?:blog_categories WHERE category_id=?i", $id);
}

function fn_blog_categories_get_category_name($category_id) {
    return db_get_field("SELECT category FROM ?:blog_categories WHERE category_id=?i", $category_id);
}
