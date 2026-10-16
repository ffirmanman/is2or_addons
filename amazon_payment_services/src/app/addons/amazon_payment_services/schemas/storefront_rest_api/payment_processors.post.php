<?php

use Tygh\Enum\Addons\StorefrontRestApi\PaymentTypes;

$schema['aps.php'] = [
    'type' => PaymentTypes::REDIRECTION,
    'class' => '\AmazonPaymentServices\Payments\APS',
];

return $schema;