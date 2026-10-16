<?php

defined('BOOTSTRAP') or die('Access denied');

if (!$auth['user_id']) {
    return [CONTROLLER_STATUS_REDIRECT, 'auth.login_form'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'generate') {
        $model_id = $_REQUEST['model_id'] ?? 0;
        $product_id = $_REQUEST['product_id'] ?? 0;

        if ($model_id && $product_id) {
            $result = fn_is2or_claid_ai_generate_tryon($product_id, $model_id);

            if ($result) {
                fn_set_notification('N', __('notice'), __('is2or_claid_ai.image_generated'));
            } else {
                fn_set_notification('E', __('error'), __('is2or_claid_ai.failed_to_generate_image'));
            }
            
            Tygh::$app['ajax']->assign('result', $result);
        }
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'index') {
    $user_id = $auth['user_id'];

    $product_id = $_REQUEST['product_id'] ?? 0;
    
    $garment_ids = '';
    if ($product_id) {
        $garmets = fn_is2or_claid_ai_get_garments($product_id);
        $garment_ids = array_column($garmets, 'garment_id');
    }
    
    $tryon_results = fn_is2or_claid_ai_get_tryon_results([
        'user_id' => $user_id,
        'product_id' => $product_id,
        'garment_ids' => $garment_ids
    ]);

    $tryon_models = fn_is2or_claid_ai_get_models($user_id);

    Tygh::$app['view']->assign('tryon_results', $tryon_results);
    Tygh::$app['view']->assign('tryon_models', $tryon_models);
}
