<?php

defined('BOOTSTRAP') or die('Access denied');

if (defined('AREA') && AREA == 'C') {
    fn_register_hooks(
        'get_orders_post'
    );
}