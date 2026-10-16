<?php

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied!');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'orders_cron') {
    $affected_rows = fn_is2or_vendor_payout_auto_complete_orders();

    fn_print_die('affected_rows=' . $affected_rows . '');
}
