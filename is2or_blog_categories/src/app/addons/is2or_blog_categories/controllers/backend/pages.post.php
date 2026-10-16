<?php

use Tygh\Tygh;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'add' || $mode == 'update') {
    $page_type = '';
    if ($mode == 'update') {
        $page_data = Tygh::$app['view']->getTemplateVars('page_data');
        $page_type = $page_data['page_type'];
    }

    if (!empty($_REQUEST['page_type'])) {
        $page_type = $_REQUEST['page_type'];
    }

    if ($page_type == PAGE_TYPE_BLOG) {
        list($block_categories) = fn_blog_categories_get_categories([]);
        Tygh::$app['view']->assign('blog_categories', $block_categories);
    }
}