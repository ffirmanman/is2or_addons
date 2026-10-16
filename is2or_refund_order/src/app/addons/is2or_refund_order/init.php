<?php
/*
 * (c) 2026 IS2OR
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Register hook to handle the RMA detail updates (saving files)
fn_register_hooks(
    [
        'change_order_status',
        'rma_add_return_post',
        'update_rma_status_post'
    ]
);