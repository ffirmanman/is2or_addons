<?php

$schema['invoice_stamp'] = [
    'class'      => '\Tygh\Template\Document\Variables\GenericVariable',
    'alias' => 'is',
    'data'       => function (\Tygh\Template\Document\Order\Context $context) {
        $order = $context->getOrder();

        return [
            'stamp' => fn_invoice_stamp_get_image_url($order->data['company_id'], 'vendor_stamp'),
            'signature' => fn_invoice_stamp_get_image_url($order->data['company_id'], 'vendor_sign')
        ];
    },
    'attributes' => [
        'stamp', 'signature'
    ]
];

return $schema;
