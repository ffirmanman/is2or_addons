<?php

use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Notifications\Transports\Mail\MailTransport;

defined('BOOTSTRAP') or die('Access denied');

$schema['cart_reminder.abandoned_cart_reminder'] = [
    'id' => 'cart_reminder.abandoned_cart_reminder',
    'group' => 'cart_reminder',
    'name' => [
        'template' => 'abandoned_cart_reminder',
        'params' => []
    ],
    'receivers' => [
        UserTypes::CUSTOMER => [
            MailTransport::getId() => MailMessageSchema::create([
                'area' => SiteArea::STOREFRONT,
                'from' => 'company_site_administrator',
                'to' => DataValue::create('email'),
                'carts' => DataValue::create('carts'),
                'url' => DataValue::create('url'),
                'currency_data' => DataValue::create('currency_data'),
                'template_code' => 'abandoned_cart_reminder',
                'template_language' => DataValue::create('lang_code', CART_LANGUAGE)
            ])
        ]
    ]
];

return $schema;
