<?php

function fn_is2or_customer_coordinates_get_order_info(&$order, $additional_data) {
    if (!empty($order['user_id']) && !empty($order['profile_id'])) {
        $user_data = fn_get_user_info($order['user_id'], true, $order['profile_id']);

        $order['s_latitude'] = $user_data['s_latitude'] ?? '';
        $order['s_longitude'] = $user_data['s_longitude'] ?? '';
        $order['b_latitude'] = $user_data['b_latitude'] ?? '';
        $order['b_longitude'] = $user_data['b_longitude'] ?? '';
    }
}
