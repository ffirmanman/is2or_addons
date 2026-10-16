<?php 

use Tygh\Tools\Url;

if ($mode == 'buy_credit') {
    fn_define('ORDER_MANAGEMENT', true);

    $cart = &Tygh::$app['session']['cart'];
    $auth = &Tygh::$app['session']['auth'];
    $vendor_id = $auth['company_id'];

    if (empty($vendor_id)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    unset($auth['act_as_user']);

    fn_clear_cart($cart, true);
    $cart['user_data'] = fn_get_user_info($auth['user_id'], true, $cart['profile_id']);

    $product_id = fn_is2or_claid_ai_get_credits_product_id();

    $product_data = fn_get_product_data($product_id, $auth);
    if (!empty($_REQUEST['credit_amount']) && is_numeric($_REQUEST['credit_amount'])) {
        $amount = $_REQUEST['credit_amount'];
        $product_cost = $amount * $product_data['price'];
    } else {
        $amount = 0;
        $product_cost = 0;
    }

    if (empty($product_cost)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $cart['is_is2or_buy_credit'] = true;

    fn_add_product_to_cart(
        [
            $product_id => [
                'product_id'      => $product_id,
                'amount'          => 1,
                'price'           => $product_cost,
                'product_options' => [],
                'stored_price'    => 'Y',
                'stored_discount' => 'Y',
                'discount'        => 0,
                'company_id'      => 0,
                'extra'           => [
                    'is2or_claid_ai' => [
                        'vendor_id' => $vendor_id,
                        'credit_amount' => $amount
                    ],
                ],
            ],
        ],
        $cart,
        $auth
    );

    list(, $redirect_params) = fn_checkout_update_steps($cart, $auth, [
        'update_step' => 'step_three',
        'next_step'   => 'step_four',
    ]);

    return [
        CONTROLLER_STATUS_REDIRECT,
        Url::buildUrn(['checkout', 'checkout'], $redirect_params),
    ];
}