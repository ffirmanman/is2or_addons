<?php

use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $id = $_REQUEST['id'] ?? 0;
        $data = $_REQUEST['data'] ?? [];

        $size_chart_id = fn_is2or_size_charts_update_chart($id, $data);
        
        return [CONTROLLER_STATUS_REDIRECT, 'size_charts.update?id=' . $size_chart_id];
    } 
    
    if ($mode == 'delete') {
        $id = $_REQUEST['id'] ?? 0;

        fn_is2or_size_charts_delete_chart($id);
        return [CONTROLLER_STATUS_REDIRECT, 'size_charts.manage'];
    }
}

if ($mode == 'manage') {
    $params = $_REQUEST;

    list($rows, $search) = fn_is2or_size_charts_get_charts(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page')
    );

    $templates = fn_is2or_size_charts_get_templates_list();

    Tygh::$app['view']->assign([
        'items' => $rows,
        'search' => $search,
        'templates' => $templates
    ]);
} else if ($mode == 'add' || $mode == 'update') {
    $company_id = Registry::get('runtime.company_id');
    $navigation = [];

    $navigation['general'] = [
        'title' => __('general'),
        'js' => true
    ];

    $allow_save = true;

    if ($mode == 'update') {
        $id = $_REQUEST['id'] ?? 0;

        $data = fn_is2or_size_charts_get_chart($id);

        if (!$data) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $navigation['variants'] = [
            'title' => __('is2or_size_charts.variants'),
            'js' => true
        ];
        $navigation['applies'] = [
            'title' => __('is2or_size_charts.applies'),
            'js' => true
        ];
        $navigation['sample'] = [
            'title' => __('is2or_size_charts.sample_chart'),
            'js' => true
        ];

        $allow_save = !$company_id || $company_id == $data['company_id'];

        $customer_sizes = fn_is2or_size_charts_get_customer_size_names();
        Tygh::$app['view']->assign('customer_sizes', $customer_sizes);
        Tygh::$app['view']->assign('data', $data);
    }

    Registry::set('navigation.tabs', $navigation);

    $templates = fn_is2or_size_charts_get_templates_list();
    Tygh::$app['view']->assign([
        'templates' => $templates,
        'allow_save' => $allow_save
    ]);
}
