<?php

use Tygh\Registry;
use Tygh\Tygh;

if ($mode == 'update' ) {
    $tabs = Registry::get('navigation.tabs');
    
    $tabs['size_chart'] = array(
        'title' => __('is2or_size_charts.size_chart'),
        'js' => true
    );
    
    Registry::set('navigation.tabs', $tabs);

    $charts = fn_is2or_size_charts_get_product_charts($_REQUEST['product_id']);
    
    $view = Tygh::$app['view'];
    $view->assign('size_charts', $charts);
}