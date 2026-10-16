<?php

defined('BOOTSTRAP') or die('Access denied');

$schema['permissions']['is2or_sms_integration'] = array(
    'top' => array(
        'is2or_sms_integration' => array(
            'manage'    => array(),
            'test_send' => array(),
        ),
    ),
);

return $schema;
