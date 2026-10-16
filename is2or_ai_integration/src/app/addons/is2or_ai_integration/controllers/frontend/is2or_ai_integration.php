<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'chat') {
    header('Content-Type: application/json; charset=UTF-8');

    if (empty($auth['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('access_denied')]);
        exit;
    }

    $message    = isset($_REQUEST['message']) ? trim((string) $_REQUEST['message']) : '';
    $session_id = isset($_REQUEST['session_id']) ? (string) $_REQUEST['session_id'] : '';
    $locale     = !empty($_REQUEST['locale']) ? strtolower($_REQUEST['locale']) : CART_LANGUAGE;

    if ($message === '') {
        echo json_encode(['success' => false, 'error' => __('text_required_fields_empty')]);
        exit;
    }
    
    $result = fn_is2or_ai_integration_call_chat($message, $session_id, $locale);

    if (empty($result['success']) || empty($result['data'])) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $data = $result['data'];

    $products_out = [];
    if (!empty($data['products']) && is_array($data['products'])) {
        $product_ids = [];
        foreach ($data['products'] as $p) {
            if (!empty($p['product_id'])) {
                $product_ids[] = (int) $p['product_id'];
            }
        }

        if (!empty($product_ids)) {
            $products_full  = fn_is2or_ai_integration_get_products_by_ids(array_unique($product_ids));
            $products_by_id = [];
            foreach ($products_full as $pf) {
                $products_by_id[$pf['product_id']] = $pf;
            }

            $seen = [];
            foreach ($data['products'] as $p) {
                $pid = !empty($p['product_id']) ? (int) $p['product_id'] : 0;
                if ($pid && isset($products_by_id[$pid]) && empty($seen[$pid])) {
                    $seen[$pid] = true;
                    $pf = $products_by_id[$pid];

                    $image_url = '';
                    if (!empty($pf['main_pair']['detailed']['image_path'])) {
                        $image_url = $pf['main_pair']['detailed']['image_path'];
                    } elseif (!empty($pf['main_pair']['icon']['image_path'])) {
                        $image_url = $pf['main_pair']['icon']['image_path'];
                    }

                    $products_out[] = [
                        'product_id' => $pid,
                        'product'    => $pf['product'],
                        'price'      => !empty($pf['price_formatted']['price']) ? $pf['price_formatted']['price'] : '',
                        'image_url'  => $image_url,
                        'url'        => fn_url('products.view?product_id=' . $pid),
                        'reason'     => !empty($p['reason']) ? $p['reason'] : '',
                    ];
                }
            }
        }
    }

    echo json_encode([
        'success'           => true,
        'session_id'        => !empty($data['session_id']) ? $data['session_id'] : $session_id,
        'reply'             => !empty($data['reply']) ? $data['reply'] : '',
        'products'          => $products_out,
        'suggested_replies' => !empty($data['suggested_replies']) ? $data['suggested_replies'] : [],
    ]);
    exit;
}

if ($mode == 'get_document') {
    $company_id = !empty($_REQUEST['company_id']) ? (int) $_REQUEST['company_id'] : 0;
    $field_id   = !empty($_REQUEST['field_id']) ? (int) $_REQUEST['field_id'] : 0;
    $filename   = !empty($_REQUEST['filename']) ? (string) $_REQUEST['filename'] : '';
    $expires    = !empty($_REQUEST['expires']) ? (int) $_REQUEST['expires'] : 0;
    $token      = !empty($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';

    if (
        empty($company_id) || empty($field_id) || $filename === ''
        || !fn_is2or_ai_integration_verify_document_token($company_id, $field_id, $filename, $expires, $token)
    ) {
        http_response_code(403);
        exit;
    }

    // Defense in depth: filename in URL must still match what's actually on record.
    $db_filename = db_get_field(
        'SELECT value FROM ?:profile_fields_data WHERE object_id = ?i AND object_type = ?s AND field_id = ?i',
        $company_id, 'S', $field_id
    );

    if (empty($db_filename) || $db_filename !== $filename) {
        http_response_code(404);
        exit;
    }

    $safe_filename = \Tygh\Tools\SecurityHelper::sanitizeFileName($filename);

    // TODO verify: base var-dir constant/registry key in this CS-Cart build.
    $base_dir = rtrim(Registry::get('config.dir.var'), '/') . '/custom_files/profile_fields/S/' . $company_id . '/';
    $full_path = $base_dir . $safe_filename;

    $real_base = realpath($base_dir);
    $real_file = realpath($full_path);

    if ($real_base === false || $real_file === false || strpos($real_file, $real_base) !== 0 || !is_file($real_file)) {
        http_response_code(404);
        exit;
    }

    $ext = strtolower(pathinfo($real_file, PATHINFO_EXTENSION));
    $mime_map = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    $mime = isset($mime_map[$ext]) ? $mime_map[$ext] : 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($real_file));
    header('Content-Disposition: inline; filename="' . $safe_filename . '"');
    header('X-Robots-Tag: noindex, nofollow');
    readfile($real_file);
    exit;
}