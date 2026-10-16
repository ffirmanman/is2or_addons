<?php

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

Tygh::$app['api'] = function ($app) {
    return new \Tygh\Addons\Is2orGoogleLogin\PublicApi();
};
