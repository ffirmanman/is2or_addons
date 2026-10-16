<?php

$schema['torod'] = [
    'name'             => 'torod_shipping',
    'template'         => 'addons/torod_shipping/views/shippings/components/services/torod.tpl',
    'handler'          => 'torod',
    'shipping_service' => 'Torod',
    'position'         => 1000,
    'file'             => 'Torod.php',
    'class'            => 'Tygh\\Shippings\\Services\\Torod',
];
return $schema;
