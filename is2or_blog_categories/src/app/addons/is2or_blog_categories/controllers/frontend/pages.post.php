<?php

use Tygh\Tygh;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'view') {
    $subpages = Tygh::$app['view']->getTemplateVars('subpages');

    if ($subpages) {
        $blog_category_ids = array_filter(array_unique(array_column($subpages, 'category_id')), function($i) {
            return $i != 0;
        });
        Tygh::$app['view']->assign('blog_category_ids', $blog_category_ids);

        if (!empty($_REQUEST['category_id'])) {
            $category_id = $_REQUEST['category_id'];

            $subpages = array_filter($subpages, function($i) use ($category_id) {
                return $i['category_id'] == $category_id;
            });

            Tygh::$app['view']->assign('subpages', $subpages);
        }
    }
}