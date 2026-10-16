<?php

$schema['tryoto'] = [
    'name' => 'tryoto_shipping',
    'template' => 'addons/tryoto_shipping/views/shippings/components/services/tryoto.tpl',
    'handler' => 'tryoto',
    'shipping_service' => 'Tryoto',
    'position' => 1000,
    'file' => 'Tryoto.php',
    'class' => 'Tygh\\Shippings\\Services\\Tryoto',
];
return $schema;
