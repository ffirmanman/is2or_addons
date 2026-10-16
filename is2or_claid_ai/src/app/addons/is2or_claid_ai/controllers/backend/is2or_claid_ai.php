<?php

use Tygh\Enum\UserTypes;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Tygh;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'upload') {
        if (!empty($_FILES['image']['tmp_name'])) {
            $file = $_FILES['image'];
            
            if ($image = fn_is2or_claid_ai_upload_image('file', $file, true)) {
                fn_echo(json_encode([
                    'url' => $image['url'],
                    'path' => $image['path']
                ]));
                
                exit;
            }
        }

        fn_echo(json_encode([]));
        fn_set_notification('E', __('error'), __('is2or_claid_ai.text_image_not_uploaded'));
        exit;
    }

    if ($mode == 'remove_tmp_image' && !empty($_REQUEST['path'])) {
        $path = $_REQUEST['path'] ?? '';

        if ($path) fn_is2or_claid_ai_delete_image($path);
    }

    if ($mode == 'process_image') {
        $path = $_REQUEST['path'] ?? '';
        $action = $_REQUEST['action'] ?? '';
        $prompt = $_REQUEST['prompt'] ?? 'Professional product photo with shadow';

        if ($path && $action) {
            if ($image = fn_is2or_claid_ai_process_image($action, $path, $prompt)) {
                fn_set_notification('N', __('notice'), __('is2or_claid_ai.text_' . $action . '_success'));

                Tygh::$app['ajax']->assign('image', $image);
                exit;
            }
        }

        fn_set_notification('E', __('error'), __('is2or_claid_ai.text_' . $action .'_failed'));
        exit;
    }

    if ($mode == 'add_product_image') {
        $product_id = (int) ($_REQUEST['product_id'] ?? 0);
        $storage_path = $_REQUEST['path'] ?? '';

        if ($product_id && $storage_path) {
            $storage = Storage::instance('images');

            if ($storage->isExist($storage_path)) {
                $ext = pathinfo($storage_path, PATHINFO_EXTENSION) ?: 'jpg';
                $unique_name = 'storage_' . uniqid('', true) . '.' . $ext;

                $dest_path = 'product/' . fn_get_image_subdir(0) . '/' . $unique_name;

                if ($storage->copy($storage_path, $dest_path)) {
                    $file_path = $storage->getAbsolutePath($dest_path);
                    $image_size = file_exists($file_path) ? filesize($file_path) : 0;

                    $image = [
                        'path' => $file_path,
                        'size' => $image_size,
                        'name' => basename($file_path),
                    ];

                    $pairs_data = [
                        [
                            'type' => 'A',
                            'image_alt' => '',
                            'position' => 0,
                        ]
                    ];

                    fn_update_image_pairs([], [$image], $pairs_data, $product_id, 'product');

                    fn_set_notification('N', __('notice'), __('is2or_claid_ai.text_add_product_image_success'));
                    exit;
                }
            }

            fn_set_notification('E', __('error'), __('is2or_claid_ai.text_add_product_image_failed'));
            exit;
        }
    }

    if ($mode == 'buy_credit') {
        if (
            $auth['user_type'] !== UserTypes::VENDOR
            || !isset($auth['company_id'])
            || !isset($_REQUEST['credit_amount'])
        ) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $buy_credit_url = fn_is2or_claid_ai_get_buy_credit_url($auth['company_id'], Tygh::$app['session']['auth'], $_REQUEST['credit_amount']);
        
        return [CONTROLLER_STATUS_REDIRECT, $buy_credit_url];
    }

    return [CONTROLLER_STATUS_OK];
}

