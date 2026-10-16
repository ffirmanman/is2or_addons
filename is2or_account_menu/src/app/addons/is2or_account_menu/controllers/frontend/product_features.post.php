<?php
if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode === 'clear_list') {
    unset(Tygh::$app['session']['ab__ecw_original_comparison']);
}