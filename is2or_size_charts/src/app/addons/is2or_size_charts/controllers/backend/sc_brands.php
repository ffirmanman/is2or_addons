<?php

use Tygh\Registry;
use Tygh\Tygh;

if ($mode == 'picker') {
    $params = $_REQUEST;
    
    list($brands, $search) = fn_is2or_size_charts_get_brands(
        $params,
        Registry::get("settings.Appearance.admin_elements_per_page")
    );

    $view = Tygh::$app['view'];
    
    $view->assign('features', fn_is2or_size_charts_get_features());
    $view->assign('brands', $brands);
    $view->assign('search', $search);
    $view->display('addons/is2or_size_charts/pickers/brands/picker_contents.tpl');
    
    exit;
}