<?php

use Tygh\Tygh;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $group_id = $_REQUEST['group_id'] ?? 0;
        $group_data = $_REQUEST['group_data'] ?? [];

        if (!empty($group_data)) {
            if (!empty($group_data['tags_ids'])) {
                $group_data['tags_ids'] = implode(',', $group_data['tags_ids']);
            }
            $group_id = fn_categories_interest_update_tags_group($group_id, $group_data);

            return [CONTROLLER_STATUS_REDIRECT, 'tags_groups.update?group_id=' . $group_id];
        }

        return [CONTROLLER_STATUS_REDIRECT, 'tags_groups.manage'];
    }

    if ($mode == 'delete') {
        $group_id = $_REQUEST['group_id'] ?? 0;

        if (!empty($group_id)) {
            fn_categories_interest_delete_tags_group($group_id);
        }

        return [CONTROLLER_STATUS_REDIRECT, 'tags_groups.manage'];
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'manage') {
    $params = $_REQUEST;
    list($groups, $search) = fn_categories_interest_get_tags_groups($params, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign([
        'groups' => $groups,
        'search' => $search
    ]);
} else if ($mode == 'add' || $mode == 'update') {
    Registry::set('navigation.tabs', [
        'general' => [
            'title' => __('general'),
            'js' => true
        ]
    ]);

    fn_add_breadcrumb(__('is2or_categories_interest.tags_groups'), 'tags_groups.manage');
    
    if (!empty($_REQUEST['group_id'])) {
        $group_id = $_REQUEST['group_id'];
        $group = fn_categories_interest_get_tags_group($group_id);

        Tygh::$app['view']->assign('group', $group);
    }

    list($tags,) = fn_get_tags();
    Tygh::$app['view']->assign('tags', $tags);
}