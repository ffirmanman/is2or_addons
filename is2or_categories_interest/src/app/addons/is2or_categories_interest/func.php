<?php

use Tygh\Registry;
use Tygh\Tygh;

function fn_is2or_categories_interest_update_profile($action, $user_data, $current_user_data) {
    $user_id = $user_data['user_id'];

    // if (!empty(fn_get_categories_interest($user_id))) {
    //     fn_clear_categories_interest($user_id);
    // }

    // if (!empty($user_data['categories_interest'])) {
    //     foreach ($user_data['categories_interest'] as $category) {
    //         fn_add_categories_interest($user_id, $category);
    //     }
    // }
    
    // if (!empty(fn_get_tags_interest($user_id))) {
    //     fn_clear_tags_interest($user_id);
    // }

    // if (!empty($user_data['tags_interest'])) {
    //     foreach($user_data['tags_interest'] as $tag) {
    //         fn_add_tags_interest($user_id, $tag);
    //     }
    // }

    if (!empty(fn_get_tags_group_interest($user_id))) {
        fn_clear_tags_group_interest($user_id);
    }

    if (!empty($user_data['tags_group_interest'])) {
        foreach($user_data['tags_group_interest'] as $tag) {
            fn_add_tags_group_interest($user_id, $tag);
        }
    }

    if ($action == 'add') {
        Tygh::$app['session']['show_interest_popup'] = true;
    } else if (isset(Tygh::$app['session']['show_interest_popup'])) {
        unset(Tygh::$app['session']['show_interest_popup']);
    }
}

function fn_is2or_categories_interest_get_products($params, $fields, $sortings, &$condition, &$join, $sorting, $group_by, $lang_code, $having) {
    if (!empty($params['tag_ids']) && Registry::get('addons.tags')) {
        if (empty($params['tag'])) {
            $join .= db_quote(" INNER JOIN ?:tag_links ON ?:tag_links.object_id = products.product_id AND ?:tag_links.object_type = ?s", 'P');
            $join .= db_quote(" INNER JOIN ?:tags ON ?:tag_links.tag_id = ?:tags.tag_id ?p", fn_get_tags_company_condition('?:tags.company_id'));

            if (AREA == 'C') {
                $condition .= db_quote(" AND ?:tags.status = ?s", 'A');
            }
        }
        
        $condition .= db_quote(" AND ?:tags.tag_id IN (?a)", $params['tag_ids']);
    }
}

function fn_is2or_categories_interest_before_dispatch($controller = '', $mode = '', $action = '', $dispatch_extra = '', $area = AREA) {
    if (
        Tygh::$app['session']['show_interest_popup'] 
        && !(
            $controller == 'profiles'
            && (
                $mode == 'update' || $mode == 'add'
            )
        )
    ) {
        $auth = & Tygh::$app['session']['auth'];

        // list($categories) = fn_get_categories(['status' => 'A']);
        // $categories_interest = fn_get_categories_interest($auth['user_id']);

        // $tags = [];
        // if (Registry::get('addons.tags')) {
        //     if (isset($_REQUEST['category_ids'])) {
        //         if (!empty($_REQUEST['category_ids'])) {
        //             $category_ids = explode(',', $_REQUEST['category_ids']);
        //         } else {
        //             $category_ids = [];
        //         }
        //     } else {
        //         $category_ids = $categories_interest;
        //     }

        //     if ($category_ids) {
        //         $tags = fn_get_products_categories_tags($category_ids);
        //     }
        // }

        // $tags_interest = fn_get_tags_interest($auth['user_id']);

        list($groups, ) = fn_categories_interest_get_tags_groups([]);
        $tags_group_interest = fn_get_tags_group_interest($auth['user_id']);

        Tygh::$app['view']->assign([
            // '_categories' => $categories,
            // '_categories_interest' => $categories_interest,
            // '_tags' => $tags,
            // '_tags_interest' => $tags_interest
            '_groups' => $groups,
            '_tags_group_interest' => $tags_group_interest
        ]);
    }
}

function fn_is2or_categories_interest_get_tags($params, $items_per_page, $fields, $joins, &$conditions, $group, $sorting) {
    if (! empty($params['item_ids'])) {
        $conditions .= db_quote(" AND ?:tags.tag_id IN (?a)", $params['item_ids']);
    }
}

function fn_get_categories_interest($user_id) {
    $data = db_get_array("SELECT category_id FROM `?:categories_interest` WHERE `user_id`=?i", $user_id);
    return array_column($data, 'category_id');
}

function fn_clear_categories_interest($user_id) {
    db_query("DELETE FROM `?:categories_interest` WHERE `user_id`=?i", $user_id);
}

function fn_add_categories_interest($user_id, $category_id) {
    db_query("INSERT INTO `?:categories_interest` ?e", [
        'user_id' => $user_id,
        'category_id' => $category_id
    ]);
}

function fn_get_tags_interest($user_id) {
    $data = db_get_array("SELECT tag_id FROM `?:tags_interest` WHERE `user_id`=?i", $user_id);
    return array_column($data, 'tag_id');
}

function fn_clear_tags_interest($user_id) {
    db_query("DELETE FROM `?:tags_interest` WHERE `user_id`=?i", $user_id);
}

function fn_add_tags_interest($user_id, $tag_id) {
    db_query("INSERT INTO `?:tags_interest` ?e", [
        'user_id' => $user_id,
        'tag_id' => $tag_id
    ]);
}

function fn_get_tags_group_interest($user_id) {
    $data = db_get_array("SELECT group_id FROM `?:tags_group_interest` WHERE `user_id`=?i", $user_id);
    return array_column($data, 'group_id');
}

function fn_clear_tags_group_interest($user_id) {
    db_query("DELETE FROM `?:tags_group_interest` WHERE `user_id`=?i", $user_id);
}

function fn_add_tags_group_interest($user_id, $tag_id) {
    db_query("INSERT INTO `?:tags_group_interest` ?e", [
        'user_id' => $user_id,
        'group_id' => $tag_id
    ]);
}

function fn_get_categories_interest_suggestion_products() {
    $auth = & Tygh::$app['session']['auth'];

    $products = [];
    // $categories_interest = fn_get_categories_interest($auth['user_id']);
    // $tags_interest = fn_get_tags_interest($auth['user_id']);
    $tags_group_interest = fn_get_tags_group_interest($auth['user_id']);
    if ($tags_group_interest) {
        $tags_ids = [];
        list($groups,) = fn_categories_interest_get_tags_groups([
            'group_ids' => $tags_group_interest
        ]);
    
        foreach ($groups as $group) {
            $tags_ids = array_unique(array_merge($tags_ids, explode(',', $group['tags_ids'])));
        }
    
        if (!empty($tags_ids)) {
            $query = fn_get_products([
                // 'cid' => $categories_interest,
                'tag_ids' => $tags_ids,
                'subcats' => 'Y',
                'get_query' => true
            ]);
        
            $product_ids = db_get_array($query . " ORDER BY RAND() LIMIT 8");
            foreach($product_ids as $product_id) {
                $products[$product_id['product_id']] = fn_get_product_data($product_id['product_id'], $auth);
            }
        }
    }

    return $products;
}

function fn_get_products_categories_tags($category_ids) {
    $products_query = fn_get_products([
        'cid' => $category_ids,
        'subcats' => 'Y',
        'get_query' => true
    ]);

    $product_data = db_get_array($products_query);
    $product_ids = array_column($product_data, 'product_id');

    $tag_data = db_get_array("SELECT DISTINCT tag_id FROM `?:tag_links` WHERE `object_id` IN (?a) AND `object_type`=?s", $product_ids, 'P');
    $tag_ids = array_column($tag_data, 'tag_id');

    list($tags) = fn_get_tags([
        'status' => 'A',
        'item_ids' => $tag_ids
    ]);

    return $tags;
}

function fn_categories_interest_get_tags_groups($params, $items_per_page = 0) {
    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page
    ];

    $params = array_merge($default_params, $params);

    $fields = [
        "`?:tags_groups`.*"
    ];
    $joins = [];
    $conditions = "";

    $sortings = [
        'group_id' => "`?:tags_groups`.`group_id`",
        'group_name' => "`?:tags_groups`.`group_name`",
    ];

    if (!empty($params['group_ids'])) {
        $conditions .= db_quote(" AND `?:tags_groups`.`group_id` IN (?a)", is_array($params['group_ids']) ? $params['group_ids'] : explode(',', $params['group_ids']));
    }

    $limit = db_paginate($params['page'], $params['items_per_page']);
    $field = implode(',', $fields);
    $join = implode(',', $joins);
    $sorting = db_sort($params, $sortings, 'group_id', 'asc');

    $query = "SELECT $field FROM `?:tags_groups` $join WHERE 1 $conditions";

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = count(db_get_array($query));
    }

    $groups = db_get_array($query . " $sorting $limit");
    fn_categories_interest_load_tags_groups_pairs($groups);
    fn_categories_interest_load_tags_groups_tags($groups);

    return [$groups, $params];
}

function fn_categories_interest_get_tags_group($group_id) {
    $group = db_get_row("SELECT * FROM `?:tags_groups` WHERE group_id=?i", $group_id);

    if ($group) {
        $group['main_pair'] = fn_get_image_pairs($group['group_id'], 'tags_group', 'M');
        $group['tags_ids'] = explode(',', $group['tags_ids']);
    }

    return $group;
}

function fn_categories_interest_load_tags_groups_pairs(&$groups) {
    foreach ($groups as $key => $group) {
        $groups[$key]['main_pair'] = fn_get_image_pairs($group['group_id'], 'tags_group', 'M');
    }
}

function fn_categories_interest_load_tags_groups_tags(&$groups) {
    foreach ($groups as $key => $group) {
        $tags = [];
        $tags_ids = explode(',', $group['tags_ids']);
        foreach ($tags_ids as $tag_id) {
            $tags[] = db_get_field("SELECT tag FROM ?:tags WHERE tag_id=?i", $tag_id);
        }
        $groups[$key]['tags'] = implode(', ', $tags);
    }
}

function fn_categories_interest_update_tags_group($group_id, $group_data) {
    if ($group_id) {
        db_query("UPDATE `?:tags_groups` SET ?u WHERE `group_id`=?i", $group_data, $group_id);
    } else {
        $group_id = db_query("INSERT INTO `?:tags_groups` ?e", $group_data);
    }

    if (fn_categories_interest_tags_group_need_image_update()) {
        fn_attach_image_pairs('tags_group', 'tags_group', $group_id);
    }

    return $group_id;
}

function fn_categories_interest_delete_tags_group($group_id) {
    db_query("DELETE FROM `?:tags_groups` WHERE `group_id`=?i", $group_id);
    fn_delete_image_pair($group_id, 'tags_group');
}

function fn_categories_interest_tags_group_need_image_update() {
    if (!empty($_REQUEST['file_tags_group_image_icon']) && is_array($_REQUEST['file_tags_group_image_icon'])) {
        $image_banner = reset($_REQUEST['file_tags_group_image_icon']);

        if ($image_banner == 'tags_group') {
            return false;
        }
    }

    return true;
}
