<?php

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if (!$auth['user_id']) {
    return [CONTROLLER_STATUS_NO_PAGE];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if ($mode == 'update') {
        $model_data = $_REQUEST['model_data'] ?? [];

        if (!empty($model_data)) {
            $model_id = fn_is2or_claid_ai_add_model($model_data);

            if ($model_id) {
                fn_set_notification('N', __('notice'), __('text_changes_saved'));
            }
        }
    }

    if ($mode == 'delete') {
        $model_id = $_REQUEST['model_id'] ?? 0;

        if ($model_id) {
            fn_is2or_claid_ai_delete_model($model_id);
            fn_set_notification('N', __('notice'), __('is2or_claid_ai.text_model_deleted'));
        }
    }

    return [CONTROLLER_STATUS_OK, 'is2or_tryon_models.index'];
}

if ($mode == 'index') {
    fn_add_breadcrumb(__('is2or_claid_ai.tryon_models'));

    $models = fn_is2or_claid_ai_get_models($auth['user_id']);
    Tygh::$app['view']->assign('models', $models);
} else if ($mode == 'add' || $mode == 'update') {

}
