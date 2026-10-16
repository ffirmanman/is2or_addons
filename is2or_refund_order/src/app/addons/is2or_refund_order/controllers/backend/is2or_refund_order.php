<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$refund_statuses = Registry::get('addons.is2or_refund_order.tab_refunded_statuses');