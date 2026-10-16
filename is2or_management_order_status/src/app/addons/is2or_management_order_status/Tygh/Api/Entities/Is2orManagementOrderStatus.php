<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;

class Is2orManagementOrderStatus extends AEntity
{
    public function index($id = '', $params = array())
    {
        $tab_map = [
            'incomplete' => 'N',
            'paid'      => array_keys(Registry::get('addons.is2or_management_order_status.tab_paid_statuses')),
            'packing'   => array_keys( Registry::get('addons.is2or_management_order_status.tab_packing_statuses')),
            'shipping'  => array_keys(Registry::get('addons.is2or_management_order_status.tab_shipping_statuses')),
            'delivered' => array_keys(Registry::get('addons.is2or_management_order_status.tab_delivered_statuses')),
            'complete'  => array_keys(Registry::get('addons.is2or_management_order_status.tab_complete_statuses')),
            'declined'  => array_keys( Registry::get('addons.is2or_management_order_status.tab_declined_statuses')),
            'cancelled'  => array_keys(Registry::get('addons.is2or_management_order_status.tab_cancelled_statuses')),
            'return'    => array_keys(Registry::get('addons.is2or_management_order_status.tab_return_statuses')),
        ];
        return array(
            'status' => Response::STATUS_OK,
            'data' => [
                'tab_map' => $tab_map,
                'statuses' => [
                    'paid_status' => Registry::get('addons.is2or_management_order_status.paid_status'),
                    'packing_status' => Registry::get('addons.is2or_management_order_status.packing_status'),
                    'shipping_status' => Registry::get('addons.is2or_management_order_status.shipping_status'),
                    'delivered_status' => Registry::get('addons.is2or_management_order_status.delivered_status'),
                    'complete_status' => Registry::get('addons.is2or_management_order_status.complete_status'),
                    'declined_status' => Registry::get('addons.is2or_management_order_status.declined_status'),
                    'cancelled_status' => Registry::get('addons.is2or_management_order_status.cancelled_status')
                ]
            ]
        );
    }

    public function create($params) { return array('status' => Response::STATUS_METHOD_NOT_ALLOWED); }

    public function update($id, $params)
    {
        $order_id = (int) $id;
        $auth = $this->auth;

        $order_info = fn_is2or_management_order_status_get_order_short_info($order_id);

        if (empty($order_info)) {
            return array(
                'status' => Response::STATUS_NOT_FOUND,
                'data' => ['message' => __('order_not_found')]
            );
        }

        $paidStatus = Registry::get('addons.is2or_management_order_status.paid_status');
        $cancelStatus = Registry::get('addons.is2or_management_order_status.cancelled_status');

        // if ($order_info['status'] === $paidStatus && (int) $auth['user_id'] === (int) $order_info['user_id']) {
        if ((int) $auth['user_id'] === (int) $order_info['user_id'] && $cancelStatus === $params['status']) {
            $status_changed = fn_change_order_status($order_id, $cancelStatus);

            if ($status_changed) {
                return array(
                    'status' => Response::STATUS_OK,
                    'data' => [
                        'message' => __('is2or_management_order_status.cancel_order_success'),
                        'order_id' => $order_id,
                        'new_status' => $cancelStatus
                    ]
                );
            }
        }

        return array(
            'status' => Response::STATUS_FORBIDDEN,
            'data' => [
                'message' => 'Order cannot be cancelled. Check ownership. current status or param status.'
            ]
        );
    }
    public function delete($id) { return array('status' => Response::STATUS_METHOD_NOT_ALLOWED); }

    public function privilegesCustomer()
    {
        return [
            'index' => true,
            'update' => true,
        ];
    }
}
