<?php
/*
 * File: app/addons/is2or_refund_order/controllers/backend/orders.post.php
 */

use Phinx\Console\Command\Status;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// File ini otomatis dipanggil SETELAH app/controllers/backend/orders.php selesai
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $mode == 'details') {
    
    // 1. Ambil Order ID dari URL
    $order_id = !empty($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
    
    // 2. Debug: Cek apakah file ini jalan
    // die("File Post-Controller Jalan untuk Order: " . $order_id);

    if ($order_id) {
        // 3. Ambil data dari database
        $refund_data = db_get_row(
            "
                SELECT * 
                FROM ?:rma_returns R 
                JOIN ?:rma_property_descriptions P
                    ON P.property_id = R.action
                    AND P.lang_code = ?i
                WHERE R.order_id = ?i LIMIT 1
            ", 
            CART_LANGUAGE,
            $order_id
        );

        switch ($refund_data['status']) {
            case 'R':
                $refund_data['status_name'] = "REQUESTED";
                break;
            
            case 'A':
                $refund_data['status_name'] = "APPROVED";
                break;
            
            case 'D':
                $refund_data['status_name'] = "DECLINED";
                break;
            
            default:
                $refund_data['status_name'] = "REQUESTED";
                break;
        }

        $refund_data['refund_url'] = fn_url("rma.details?return_id={$refund_data['return_id'] }");
        if (!empty($refund_data)) {
            // 4. Masukkan langsung ke variabel Smarty
            Tygh::$app['view']->assign('refund_details', $refund_data);
        }


        $messages = db_get_row(
            "
                SELECT *    
                FROM ?:vendor_communications V 
                JOIN ?:vendor_communication_messages VM
                ON VM.thread_id = V.thread_id
                WHERE V.object_type = 'O'
                AND V.object_id = ?i
            ", 
            $order_id
        );
        if (!empty($refund_data)) {
            // 4. Masukkan langsung ke variabel Smarty
            Tygh::$app['view']->assign('order_messages', $messages);
        }

    }
}