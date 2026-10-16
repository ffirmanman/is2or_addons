<?php
use Tygh\ContextMenu\Items\GroupItem;

$schema['items']['quotation_status'] = [
    'name'     => ['template' => 'quotation_status'],
    'type'     => GroupItem::class,
    'items'    => [
        'make_active'   => [
            'name'          => [
                'template' => 'change_to_status',
                'params'   => [
                    '[status]' => __('active')
                ],
            ],
            'dispatch' => 'sg_quotation.m_activate',
            'position' => 10,
        ],
        'make_disabled' => [
            'name'     => [
                'template' => 'change_to_status',
                'params'   => [
                    '[status]' => __('disabled')
                ],
            ],
            'dispatch' => 'sg_quotation.m_disable',
            'position' => 20,
        ],
    ],
    'position' => 40,
];

return $schema;