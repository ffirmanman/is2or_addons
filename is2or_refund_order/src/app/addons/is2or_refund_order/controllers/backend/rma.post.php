<?php

use Tygh\Enum\Addons\Rma\ReturnOperationStatuses;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if ($mode == 'update_details') {
        $data = $_REQUEST['change_return_status'];
        $return_id = $data['return_id'];
        $status_to = $data['status_to'];

        if ($status_to == 'A') {            
            $target_rma_statuses = [ReturnOperationStatuses::APPROVED];

            $refunded_status = db_get_field("
                SELECT A.status
                FROM ?:statuses A
                JOIN ?:status_descriptions B
                ON B.status_id = A.status_id
                WHERE A.type = 'O'
                AND LOWER(B.description) = 'refunded'
            ");
            
            if (empty($refunded_status)) {
                return;
            }

            if (in_array($status_to, $target_rma_statuses)) {
                $order_id = db_get_field("SELECT order_id FROM ?:rma_returns WHERE return_id = ?i", $return_id);

                if (!empty($order_id)) {
                    fn_change_order_status($order_id, $refunded_status);
                }
            }
        }
    }
}