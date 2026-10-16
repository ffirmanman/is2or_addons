<?php

$schema['is2or_loan_calculator'] = [
    'content' => [
        'category' => array(
            'type' => 'picker',
            'params' => array(
                'plain' => true,
                'simple' => false,
                'group_by_level' => false,
            ),
            'picker' => 'pickers/categories/picker.tpl',
            'picker_params' => array(
                'multiple' => false,
                'use_keys' => 'N',
                'status' => 'A',
                'positions' => true,
            ),
        ),
        'filter_id' => array(
            'type' => 'function',
            'function' => array('is2or_loan_calculator_get_filter_id'),
        )
    ],
    'templates' => 'addons/is2or_loan_calculator/blocks/emi_calculator.tpl',
    'wrappers' => 'blocks/wrappers',
    'cache' => false,
];


return $schema;
