<?php

use Tygh\Registry;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update_categories_interest') {
        $auth = & Tygh::$app['session']['auth'];

        fn_is2or_categories_interest_update_profile('update', $_REQUEST['user_data'], $auth);

        return [CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url'] ?? ''];
    }

    return;
}

if ($mode == 'add' || $mode == 'update') {
    list($groups,) = fn_categories_interest_get_tags_groups([]);
    Tygh::$app['view']->assign('groups', $groups);

    $tags_group_interest = fn_get_tags_group_interest($auth['user_id']);
    Tygh::$app['view']->assign('tags_group_interest', $tags_group_interest);

    // fn_print_die($groups);

    // fn_print_die($groups);
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

    // Tygh::$app['view']->assign([
    //     'categories' => $categories,
    //     'categories_interest' => $categories_interest,
    //     'tags' => $tags,
    //     'tags_interest' => $tags_interest
    // ]);
}
// else if ($mode == 'tags_interest') {
//     if (!defined('AJAX_REQUEST')) {
//         return [CONTROLLER_STATUS_NO_PAGE];
//     }
    
//     $tags = [];
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

//     echo json_encode($tags);
//     exit;
// } 
else if ($mode == 'close_interest_popup') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    unset(Tygh::$app['session']['show_interest_popup']);

    return [CONTROLLER_STATUS_OK];
}