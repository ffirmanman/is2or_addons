<?php

use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $data = $_REQUEST['data'];
        $id   = $_REQUEST['id'];
        
        $id = fn_is2or_size_charts_update_template($id, $data);
        
        return [CONTROLLER_STATUS_OK, 'size_chart_templates.update?id='.$id];
    } else if ($mode == 'delete') {
        $id = $_REQUEST['id'];
        
        fn_is2or_size_charts_delete_template($id);
        return [CONTROLLER_STATUS_OK, 'size_chart_templates.manage'];
    }
}

if ($mode == 'add' || $mode == 'update') {
    $navigation = [];
    $navigation['general'] = [
        'title' => __('general'),
        'js' => true
    ];

    Registry::set('navigation.tabs', $navigation);

    if ($mode == 'update') {
        $id = $_REQUEST['id'];
        
        $template = fn_is2or_size_charts_get_template($id);
        if (!$template)
            return [CONTROLLER_STATUS_NO_PAGE];

        Tygh::$app['view']->assign('data', $template);
    }
} else if ($mode == 'manage') {
    $params = $_REQUEST;

    list($templates, $search) = fn_is2or_size_charts_get_templates(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page')
    );

    Tygh::$app['view']->assign([
        'items' => $templates,
        'search' => $search
    ]);
}