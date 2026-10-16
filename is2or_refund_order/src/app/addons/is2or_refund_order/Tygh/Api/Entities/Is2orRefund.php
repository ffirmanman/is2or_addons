<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;
use Tygh\Enum\Addons\Rma\ReturnOperationStatuses;
use Tygh\Enum\Addons\Rma\RecalculateOperations;

class Is2orRefund extends AEntity
{
    /**
     * GET /is2or_refund?order_id=123
     * Retrieve RMA metadata (reasons, actions) and order products
     */
    public function index($id = '', $params = [])
    {
        $user_id  = (int) $this->auth['user_id'];
        $order_id = !empty($params['order_id']) ? (int) $params['order_id'] : 0;

        if (empty($order_id)) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => ['message' => 'Order ID is required']
            ];
        }

        \fn_load_addon('rma');

        $order_info = \fn_get_order_info($order_id);
        if (empty($order_info) || $order_info['user_id'] != $user_id) {
            return ['status' => Response::STATUS_FORBIDDEN];
        }

        $reasons = \fn_get_rma_properties(
            defined('RMA_REASON') ? RMA_REASON : 'R',
            CART_LANGUAGE
        );

        $actions = \fn_get_rma_properties(
            defined('RMA_ACTION') ? RMA_ACTION : 'A',
            CART_LANGUAGE
        );

        return [
            'status' => Response::STATUS_OK,
            'data'   => [
                'order_id' => $order_id,
                'reasons'  => $reasons,
                'actions'  => $actions,
                'products' => array_values($order_info['products'])
            ]
        ];
    }

    /**
     * POST /is2or_refund
     * Create refund request (RMA) with optional evidence upload
     */
    public function create($params)
    {
        $user_id  = (int) $this->auth['user_id'];
        $order_id = !empty($params['order_id']) ? (int) $params['order_id'] : 0;

        if (empty($order_id) || empty($params['returns'])) {
            return ['status' => Response::STATUS_BAD_REQUEST, 'data' => ['message' => 'Missing data']];
        }

        \fn_load_addon('rma');
        $order_info = \fn_get_order_info($order_id);

        if (empty($order_info) || $order_info['user_id'] != $user_id) {
            return ['status' => Response::STATUS_FORBIDDEN, 'data' => ['message' => 'Access denied']];
        }
        
        // --- 1. UPSERT RMA HEADER ---
        $return_id = \db_get_field(
            "SELECT return_id FROM ?:rma_returns WHERE order_id = ?i AND user_id = ?i AND status = ?s",
            $order_id, $user_id, ReturnOperationStatuses::REQUESTED
        );

        $rma_data = [
            'order_id'  => $order_id,
            'user_id'   => $user_id,
            'action'    => !empty($params['return_action']) ? $params['return_action'] : 1,
            'comment'   => !empty($params['comment']) ? $params['comment'] : '',
            'status'    => ReturnOperationStatuses::REQUESTED,
            'timestamp' => time(),
        ];

        if (!empty($return_id)) {
            \db_query("UPDATE ?:rma_returns SET ?u WHERE return_id = ?i", $rma_data, $return_id);
            // Clean up old products & old extra data before re-insert
            $old_items = \db_get_fields("SELECT item_id FROM ?:rma_return_products WHERE return_id = ?i", $return_id);
            foreach ($old_items as $i_id) {
                $this->updateOrderDetailsExtra($order_id, $i_id, $return_id, null); // Remove old ref
            }
            \db_query("DELETE FROM ?:rma_return_products WHERE return_id = ?i", $return_id);
        } else {
            $return_id = \db_query("INSERT INTO ?:rma_returns ?e", $rma_data);
        }

        // --- 2. INSERT PRODUCTS & SYNC ORDER DETAILS ---
        $total_refund_amount = 0;
        $has_items = false;

        foreach ($params['returns'] as $item_id => $item_data) {
            if (!empty($item_data['chosen']) && $item_data['chosen'] === 'Y' && isset($order_info['products'][$item_id])) {
                $order_item = $order_info['products'][$item_id];

                // VALIDATION: Check quantity
                $max_available = $this->getReturnableAmount($order_item, $return_id);
                $amount_to_return = !empty($item_data['amount']) ? (int) $item_data['amount'] : $max_available;

                if ($amount_to_return > $max_available) {
                    $amount_to_return = $max_available;
                }

                if ($amount_to_return <= 0) continue;
                

                $product_return_data = [
                    'return_id' => $return_id,
                    'item_id'   => $item_id,
                    'product_id'=> $order_item['product_id'],
                    'amount'    => $amount_to_return,
                    'type'      => 'A',
                    'reason'    => !empty($item_data['reason']) ? (int) $item_data['reason'] : 1,
                    'price'     => $order_item['price'],
                    'product'   => $order_item['product']
                ];
                \db_query("INSERT INTO ?:rma_return_products ?e", $product_return_data);

                // Update order_details extra
                $this->updateOrderDetailsExtra($order_id, $item_id, $return_id, $amount_to_return);

                $total_refund_amount += $amount_to_return;
                $has_items = true;
            }
        }

        if ($has_items) {
            \db_query("UPDATE ?:rma_returns SET total_amount = ?i WHERE return_id = ?i", $total_refund_amount, $return_id);

            $update_data = [
                'confirmed' => 'Y',
                'change_return_status' => [
                    'return_id'         => $return_id,
                    'order_id'          => $order_id,
                    'status_from'       => ReturnOperationStatuses::REQUESTED,
                    'status_to'         => ReturnOperationStatuses::REQUESTED,
                    'action'            => !empty($params['return_action']) ? $params['return_action'] : 1,
                    'recalculate_order' => RecalculateOperations::AUTO
                ]
            ];
            \fn_rma_update_details($update_data);

            if (function_exists('fn_is2or_refund_order_rma_add_return_post')) {
                \fn_is2or_refund_order_rma_add_return_post($return_id, $order_info, $params);
            }

            return [
                    'status' => Response::STATUS_OK,
                    'data'   => [
                        'return_id'    => $return_id,
                        'total_amount' => $total_refund_amount,
                        'message'      => 'Refund request processed successfully'
                    ]
                ];
        }

        return ['status' => Response::STATUS_BAD_REQUEST];
    }

    /**
     * Helper
     */
    private function updateOrderDetailsExtra($order_id, $item_id, $return_id, $amount = null) {
        $extra = \db_get_field("SELECT extra FROM ?:order_details WHERE item_id = ?i AND order_id = ?i", $item_id, $order_id);
        $extra = unserialize($extra);

        if ($amount === null) {
            unset($extra['returns'][$return_id]);
        } else {
            $extra['returns'][$return_id] = [
                'amount' => $amount,
                'status' => ReturnOperationStatuses::REQUESTED
            ];
        }
        \db_query("UPDATE ?:order_details SET extra = ?s WHERE item_id = ?i AND order_id = ?i", serialize($extra), $item_id, $order_id);
    }

    /**
     * Helper: Count quantity
     */
    private function getReturnableAmount($order_item, $current_return_id)
    {
        $already_returned = 0;
        if (!empty($order_item['extra']['returns'])) {
            foreach ($order_item['extra']['returns'] as $r_id => $r_data) {
                // Jangan hitung return_id yang sedang kita proses sekarang
                if ($r_id != $current_return_id) {
                    $already_returned += (int) $r_data['amount'];
                }
            }
        }
        return (int) $order_item['amount'] - $already_returned;
    }

    public function update($id = '', $params = [])
    {
        return ['status' => Response::STATUS_METHOD_NOT_ALLOWED];
    }

    public function delete($id = '', $params = [])
    {
        return ['status' => Response::STATUS_METHOD_NOT_ALLOWED];
    }

    public function privileges()
    {
        return ['index' => true, 'create' => true];
    }

    public function privilegesCustomer()
    {
        return ['index' => true, 'create' => true];
    }
}
