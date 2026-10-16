<?php
defined('BOOTSTRAP') or die('Access denied');

use Tygh\Tygh;
use Tygh\Notifications\Transports\Whatsapp\WhatsappTransport;

// Daftarkan transport ke dalam container
Tygh::$app['event.transports.whatsapp'] = function ($app) {
    return new WhatsappTransport();
};
