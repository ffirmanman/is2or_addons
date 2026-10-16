<?php

defined('BOOTSTRAP') or die('Access denied');

if (!empty($_SESSION['is2or_sms_otp_pending']['user_id'])) {
    return array(CONTROLLER_STATUS_REDIRECT, 'is2or_sms_integration.otp_verify');
}