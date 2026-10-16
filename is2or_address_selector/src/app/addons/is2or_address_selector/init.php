<?php

defined('BOOTSTRAP') or die('Access denied');

fn_register_hooks(
    'get_profile_fields_post',
    'update_profile'
);
