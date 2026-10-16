<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mode == 'add_return') {
    $return_id = db_get_field("SELECT MAX(return_id) FROM ?:rma_returns");
    
    if ($return_id) {
        fn_is2or_refund_order_rma_add_return_post($return_id, [], []);
    }
}