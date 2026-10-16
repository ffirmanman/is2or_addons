<?php

use Tygh\ContextMenu\Items\ActionItem;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied!');

$active_tab = Tygh::$app['view']->getTemplateVars('active_tab');


if (isset($schema['items']['actions']['items']['delete_selected'])) {
    unset($schema['items']['actions']['items']['delete_selected']);
}

if (isset($schema['items']['actions']['items']['actions_divider_2'])) {
    unset($schema['items']['actions']['items']['actions_divider_2']);
}



$schema['selectable_statuses'] = [];
$filtered_statuses = [];
$all_statuses = Tygh::$app['view']->getTemplateVars('order_status_descr');

if ($active_tab === 'paid') {
    $packing_status_code = Registry::get('addons.is2or_management_order_status.packing_status');
    $declined_status_code = Registry::get('addons.is2or_management_order_status.declined_status');

    if (isset($all_statuses[$packing_status_code])) {
        $filtered_statuses[$packing_status_code] = $all_statuses[$packing_status_code];
    }
    if (isset($all_statuses[$declined_status_code])) {
        $filtered_statuses[$declined_status_code] = $all_statuses[$declined_status_code];
    }


}else if ($active_tab === 'packing') {
    // only show  the shipping status
//    $allowStatus = Registry::get('addons.is2or_management_order_status.shipping_status');
//    if (isset($all_statuses[$allowStatus])) {
//        $filtered_statuses[$allowStatus] = $all_statuses[$allowStatus];
//    }

    $schema['items']['actions']['items']['actions_divider_3'] = [
        'type'     => DividerItem::class,
        'position' => 40,
    ];
    $schema['items']['actions']['items']['create_shipments'] = [
        'name'     => ['template' => 'create_shipments'],
        'dispatch' => 'is2or_order_management.create_shipments',
        'data'     => [
            'action_class'    => 'cm-confirm'
        ],
        'position' => 50,
    ];
}

if (empty($filtered_statuses)) {
    if (isset($schema['items']['status'])) {
        unset($schema['items']['status']);
    }
}

Tygh::$app['view']->assign('order_status_descr', $filtered_statuses);

return $schema;
