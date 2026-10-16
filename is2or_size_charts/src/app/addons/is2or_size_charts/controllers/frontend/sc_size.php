<?php

defined('BOOTSTRAP') or die('Access denied');

if (empty($auth['user_id']))
    return [CONTROLLER_STATUS_REDIRECT, 'auth.login_form'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = $_REQUEST['data'] ?? [];
    
    fn_is2or_size_charts_update_user_sizes($auth['user_id'], $data);
    return [CONTROLLER_STATUS_REDIRECT, 'sc_size.index'];
}

if ($mode == 'index') {
    fn_add_breadcrumb(__('is2or_size_charts.my_size'));

    $user_data = fn_get_user_info($auth['user_id']);
    $customer_sizes = fn_is2or_size_charts_get_customer_size_names();

    Tygh::$app['view']->assign('user_data', $user_data);
    Tygh::$app['view']->assign('customer_sizes', $customer_sizes);
}