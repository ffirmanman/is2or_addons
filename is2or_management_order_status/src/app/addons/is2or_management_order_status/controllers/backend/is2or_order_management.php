<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'create_shipments' && !empty($_REQUEST['order_ids'])) {
        foreach ($_REQUEST['order_ids'] as $order_id) {
            $order_info = fn_get_order_info($order_id);

            if (empty($order_info)) {
                continue;
            }
            $params['notify_user'] = 'Y';
            $force_notification = fn_get_notification_rules($params);

            $shipping_ids = $order_info['shipping_ids'];
            if (!is_array($shipping_ids)) {
                $shipping_ids = !empty($shipping_ids) ? explode(',', $shipping_ids) : [];
            }

            $selected_shipping = [];
            if (!empty($order_info['shipping']) && is_array($order_info['shipping'])) {
                $selected_shipping = reset($order_info['shipping']) ?: [];
            }

            $shipping_id = !empty($selected_shipping['shipping_id'])
                ? (int) $selected_shipping['shipping_id']
                : (!empty($shipping_ids) ? (int) reset($shipping_ids) : 0);

            $carrier = $selected_shipping['module'] ?? '';

            if ($carrier === '' && $shipping_id) {
                $shipping_info = fn_get_shipping_info($shipping_id);
                $carrier = $shipping_info['module'] ?? '';

                if ($carrier === '' && !empty($shipping_info['service_id'])) {
                    $service = fn_get_shipping_service_data((int) $shipping_info['service_id']);
                    $carrier = $service['module'] ?? '';
                }
            }

            if ($carrier === '') {
                fn_set_notification('E', __('error'), __('text_not_available'));
                continue;
            }

            $shipment_data = [
                'order_id'    => $order_id,
                'shipping_id' => $shipping_id,
                'carrier'     => $carrier,
                'order_status' => Registry::get('addons.is2or_management_order_status.shipping_status'),
                'comments'    => '',
                'products'    => []
            ];

            if (!empty($order_info['products'])) {
                foreach ($order_info['products'] as $item_id => $product) {
                    $shipment_data['products'][$item_id] = $product['amount'];
                }
            }

            fn_update_shipment($shipment_data, 0, 0, false, $force_notification);
        }
        $suffix = '.manage';
        return array(CONTROLLER_STATUS_OK, 'is2or_order_management' . $suffix);
    }
}

if ($mode == 'manage') {
    $active_tab = !empty($_REQUEST['selected_tab']) ? $_REQUEST['selected_tab'] : 'paid';

    $tab_map = [
        'incomplete' => ['N' => 'Y'],
        'paid'      => Registry::get('addons.is2or_management_order_status.tab_paid_statuses'),
        'packing'   => Registry::get('addons.is2or_management_order_status.tab_packing_statuses'),
        'shipping'  => Registry::get('addons.is2or_management_order_status.tab_shipping_statuses'),
        'delivered' => Registry::get('addons.is2or_management_order_status.tab_delivered_statuses'),
        'complete'  => Registry::get('addons.is2or_management_order_status.tab_complete_statuses'),
        'declined'  => Registry::get('addons.is2or_management_order_status.tab_declined_statuses'),
        'cancelled'  => Registry::get('addons.is2or_management_order_status.tab_cancelled_statuses'),
        'return'    => Registry::get('addons.is2or_management_order_status.tab_return_statuses'),
    ];

    $params = $_REQUEST;

    // 3. Logika Filter Status
    if ($active_tab !== 'all' && isset($tab_map[$active_tab])) {
        $status_setting = $tab_map[$active_tab];

        if (is_array($status_setting)) {
            $params['status'] = array_keys($status_setting);
        } elseif (!empty($status_setting)) {
            $params['status'] = explode(',', str_replace(' ', '', $status_setting));
        } else {
            $params['status'] = ['NONE'];
        }
    } else {
        $params['status'] = ''; // Untuk tab 'all'
    }
    $company_id = fn_get_runtime_company_id();
    $condition = !empty($company_id) ? db_quote(" AND company_id = ?i", $company_id) : "";

    $status_counts = db_get_hash_single_array(
        "SELECT status, COUNT(*) as qty FROM ?:orders WHERE 1 ?p GROUP BY status",
        ['status', 'qty'],
        $condition
    );

    $tab_counts = [];
    $count_params = $_REQUEST;
    $count_params['get_counts'] = true; // Flag jika diperlukan di hook
    $count_params['items_per_page'] = 1;
    if ($company_id) {
        $count_params['company_id'] = $company_id;
    }

    foreach ($tab_map as $tab => $statuses) {
        $count_params['status'] = array_keys($statuses);
        list(, $search, ) = fn_get_orders($count_params, 1, false);
        $tab_counts[$tab] = $search['total_items'] ?? 0;
    }

    $params['include_incompleted'] = true;

    if (fn_allowed_for('MULTIVENDOR')) {
        $params['company_name'] = true;
    }

    if (isset($params['phone'])) {
        $params['phone'] = str_replace(' ', '', preg_replace('/[^0-9\s]/', '', $params['phone']));
    }
    $params['extra'][] = 'user_type';
    $params['extra'][] = 'issuers';

    if ($company_id) {
        $params['company_id'] = $company_id;
    }

    $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');

    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_elements_per_page'), true);

    Tygh::$app['view']->assign([
        'orders'            => $orders,
        'search'            => $search,
        'active_tab'        => $active_tab,
        'totals'            => $totals,
        'order_statuses'    => fn_get_statuses(STATUSES_ORDER, [], true, false, CART_LANGUAGE),
        'order_status_descr'=> fn_get_simple_statuses(STATUSES_ORDER, true, true),
        'tab_counts'        => $tab_counts,
    ]);

    if (fn_allowed_for('MULTIVENDOR')) {
        Tygh::$app['view']->assign('selected_storefront_id', empty($_REQUEST['storefront_id']) ? 0 : (int) $_REQUEST['storefront_id']);
    }
}


