<?php

use Tygh\Settings;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update' && $_REQUEST['addon'] == 'is2or_claid_ai') {
        fn_trusted_vars('is2or_settings');

        $is2or_settings = $_REQUEST['is2or_settings'];

        foreach ($is2or_settings as $key => $value) {
            if ($key == 'credit_price') {
                fn_update_product_prices(fn_is2or_claid_ai_get_credits_product_id(), [
                    'price' => $value
                ]);
            }

            Settings::instance()->updateValue($key, $value, 'is2or_claid_ai');
        }
    }

    return [CONTROLLER_STATUS_OK];
}