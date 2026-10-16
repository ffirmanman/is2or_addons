<?php

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * "Just for You" — personalized product recommendations block.
 *
 * Content is generated per logged-in customer by calling
 * fn_is2or_ai_integration_get_for_customer_products_block(), which hits
 * /recommend/for-customer/{user_id}. If the visitor is not logged in,
 * the function returns an empty array, so the block simply renders nothing
 * (see the {if $products} check inside the template).
 */
$schema['is2or_for_customer_products'] = array(
    'content' => array(
        'products' => array(
            'type'     => 'function',
            'function' => array('fn_is2or_ai_integration_get_for_customer_products_block'),
        ),
    ),

    'templates' => array(
        'addons/is2or_ai_integration/for_customer_products.tpl' => array(),
    ),

    'wrappers' => 'blocks/wrappers',

    // Content is user-specific (depends on who is logged in), so it must
    // never be cached/shared between different customers.
    'cache' => false,
);

return $schema;
