<?php

defined('BOOTSTRAP') or die('Access denied');

$schema['is2or_vendor_list'] = [
    'content' => [
        'vendors' => [
            'type' => 'function',
            'function' => ['fn_is2or_vendor_list_get_block_vendors'],
        ],
        'limit' => [
            'type' => 'input',
            'default_value' => '0',
            'option_name' => 'is2or_vendor_list.limit',
            'tooltip' => __('is2or_vendor_list.limit.tooltip'),
        ],
        'sort' => [
            'type' => 'selectbox',
            'values' => [
                'company' => 'is2or_vendor_list.sort.company',
            ],
            'default_value' => '',
            'option_name' => 'is2or_vendor_list.sort',
        ],
        'category_ids' => [
            'type' => 'picker',
            'default_value' => '',
            'picker' => 'pickers/categories/picker.tpl',
            'picker_params' => [
                'multiple' => true,
                'use_keys' => 'N',
                'status' => 'A',
                'no_item_text' => __('is2or_vendor_list.all_categories'),
            ],
            'option_name' => 'is2or_vendor_list.category_filter',
            'tooltip' => __('is2or_vendor_list.category_filter.tooltip'),
        ],
    ],
    'settings' => [
        'use_block_title' => [
            'type' => 'checkbox',
            'default_value' => 'N',
            'option_name' => 'is2or_vendor_list.use_block_title',
            'tooltip' => __('is2or_vendor_list.use_block_title.tooltip'),
        ],
    ],
    'templates' => [
        'addons/is2or_vendor_list/blocks/vendor_list.tpl' => [
            'name' => __('is2or_vendor_list.scrolled_list'),
        ],
        'addons/is2or_vendor_list/blocks/vendor_page_list.tpl' => [
            'name' => __('is2or_vendor_list.grid_layout'),
        ],
    ],
    'wrappers' => 'blocks/wrappers',
    'cache' => false,
];

return $schema;
