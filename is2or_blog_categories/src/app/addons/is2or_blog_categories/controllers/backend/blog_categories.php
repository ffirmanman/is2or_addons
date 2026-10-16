<?php

use Tygh\Registry;
use Tygh\Tygh;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $id = $_REQUEST['category_id'] ?? 0;
        $data = $_REQUEST['category_data'];

        if (!empty($data)) {
            fn_blog_categories_update_category($id, $data);
        }

        return [CONTROLLER_STATUS_OK, 'blog_categories.manage'];
    }

    if ($mode == 'delete') {
        $id = $_REQUEST['category_id'];

        fn_blog_categories_delete_category($id);
        fn_set_notification('N', __('notice'), __('is2or_blog_categories.text_category_deleted'));

        return [CONTROLLER_STATUS_OK, 'blog_categories.manage'];
    }

    if ($mode == 'm_update') {
        $categories = $_REQUEST['categories'] ?? [];

        if (!empty($categories)) {
            foreach ($categories as $category_id => $data) {
                fn_blog_categories_update_category($category_id, $data);
            }
        }

        return [CONTROLLER_STATUS_OK, 'blog_categories.manage'];
    }

    if ($mode == 'm_delete') {
        $category_ids = $_REQUEST['category_ids'] ?? [];

        if (!empty($category_ids)) {
            foreach ($category_ids as $category_id) {
                fn_blog_categories_delete_category($category_id);
            }

            fn_set_notification('N', __('notice'), __('is2or_blog_categories.text_category_deleted'));
            return [CONTROLLER_STATUS_OK, 'blog_categories.manage'];
        }
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'manage') {
    $params = $_REQUEST;

    list($categories, $search) = fn_blog_categories_get_categories($params, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign([
        'categories' => $categories,
        'search' => $search
    ]);
}
