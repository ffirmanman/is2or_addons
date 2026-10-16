<?php

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if (in_array($mode, ['image_cleanup_start', 'image_cleanup_apply']) && !empty($_REQUEST['product_id'])) {
    $company_id = fn_get_product_company_id((int) $_REQUEST['product_id']);
    if ($company_id != $auth['company_id']) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => __('access_denied')]);
        exit;
    }
}

/**
 * dispatch=is2or_ai_integration.generate_listing
 *
 */
if ($mode == 'generate_listing') {
    if (empty($auth['area']) || $auth['area'] !== 'A' || empty($auth['user_id'])) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_forbidden')]);
        exit;
    }

    $product_name = isset($_REQUEST['product_name']) ? trim((string) $_REQUEST['product_name']) : '';
    $brand        = isset($_REQUEST['brand']) ? (string) $_REQUEST['brand'] : '';
    $notes        = isset($_REQUEST['notes']) ? (string) $_REQUEST['notes'] : '';
    $product_id   = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    $features = [];
    if (!empty($_REQUEST['features']) && is_string($_REQUEST['features'])) {
        $features = preg_split('/[\r\n]+/', $_REQUEST['features']);
    } elseif (!empty($_REQUEST['features']) && is_array($_REQUEST['features'])) {
        $features = $_REQUEST['features'];
    }

    $result = fn_is2or_ai_integration_call_listing($product_name, $brand, $notes, (array) $features, $product_id);

    header('Content-Type: application/json; charset=UTF-8');

    if (empty($result['success'])) {
        echo json_encode([
            'success' => false,
            'error'   => $result['error'] ?: __('is2or_ai_integration.error_bad_response'),
        ]);
        exit;
    }

    $categorize_description = '';
    if (!empty($result['output']['description_en'])) {
        $categorize_description = trim(strip_tags($result['output']['description_en']));
    } elseif (!empty($result['output']['description_ar'])) {
        $categorize_description = trim(strip_tags($result['output']['description_ar']));
    } elseif ($notes !== '') {
        $categorize_description = $notes;
    }

    echo json_encode([
        'success'         => true,
        'output'          => $result['output'],
        'review_required' => !empty($result['review_required']),
        'suggestion_id'   => $result['suggestion_id'] ?? null,
    ]);
    exit;
}

if ($mode == 'generate_categorize') {
    if (empty($auth['area']) || $auth['area'] !== 'A' || empty($auth['user_id'])) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_forbidden')]);
        exit;
    }

    $product_name = isset($_REQUEST['product_name']) ? trim((string) $_REQUEST['product_name']) : '';
    $description  = isset($_REQUEST['description']) ? (string) $_REQUEST['description'] : '';
    $product_id   = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    $categorize_result = fn_is2or_ai_integration_call_categorize($product_name, $description, $product_id);

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success'    => !empty($categorize_result['success']),
        'categories' => !empty($categorize_result['success']) ? $categorize_result['output'] : null,
        'error'      => $categorize_result['error'] ?? null,
    ]);
    exit;
}

if ($mode == 'generate_details') {
    if (empty($auth['area']) || $auth['area'] !== 'A' || empty($auth['user_id'])) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_forbidden')]);
        exit;
    }

    $product_name = isset($_REQUEST['product_name']) ? trim((string) $_REQUEST['product_name']) : '';
    $brand        = isset($_REQUEST['brand']) ? (string) $_REQUEST['brand'] : '';
    $description  = isset($_REQUEST['description']) ? (string) $_REQUEST['description'] : '';
    $notes        = isset($_REQUEST['notes']) ? (string) $_REQUEST['notes'] : '';
    $product_id   = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    $result = fn_is2or_ai_integration_call_attributes($product_name, $product_id, $brand, $description, $notes);

    header('Content-Type: application/json; charset=UTF-8');

    if (empty($result['success'])) {
        echo json_encode([
            'success' => false,
            'error'   => $result['error'] ?: __('is2or_ai_integration.error_bad_response'),
        ]);
        exit;
    }

    echo json_encode([
        'success'         => true,
        'output'          => $result['output'],
        'review_required' => !empty($result['review_required']),
        'suggestion_id'   => $result['suggestion_id'] ?? null,
    ]);
    exit;
}


if ($mode == 'risky_listings') {
    $status = !empty($_REQUEST['status']) ? $_REQUEST['status'] : 'pending_review';

    $queue = fn_is2or_ai_integration_get_review_queue('risky_listing', $status);

    $product_ids = [];
    foreach ($queue as $row) {
        if (!empty($row['input']['listing']['product_id'])) {
            $product_ids[] = (int) $row['input']['listing']['product_id'];
        }
    }

    $products_by_id = [];
    if (!empty($product_ids)) {
        foreach (fn_is2or_ai_integration_get_products_by_ids(array_unique($product_ids)) as $p) {
            $products_by_id[$p['product_id']] = $p;
        }
    }

    $rows = [];
    foreach ($queue as $row) {
        $pid = !empty($row['input']['listing']['product_id']) ? (int) $row['input']['listing']['product_id'] : 0;

        $rows[] = [
            'id'           => $row['id'],
            'product_id'   => $pid,
            'product_name' => $pid && isset($products_by_id[$pid])
                ? $products_by_id[$pid]['product']
                : (!empty($row['input']['listing']['name']) ? $row['input']['listing']['name'] : ('#' . $pid)),
            'risk'         => !empty($row['output']['risk']) ? $row['output']['risk'] : '',
            'action'       => !empty($row['output']['action']) ? $row['output']['action'] : '',
            'reasons'      => !empty($row['output']['reasons']) ? $row['output']['reasons'] : [],
            'score'        => isset($row['output']['score']) ? $row['output']['score'] : null,
            'status'       => $row['status'],
        ];
    }

    Tygh::$app['view']->assign('rows', $rows);
    Tygh::$app['view']->assign('queue_status', $status);
}

if ($mode == 'risky_listings_approve' || $mode == 'risky_listings_reject') {
    $suggestion_id = !empty($_REQUEST['suggestion_id']) ? (int) $_REQUEST['suggestion_id'] : 0;

    if ($suggestion_id && !empty($auth['user_id'])) {
        $reviewed_by = !empty($auth['user_login']) ? $auth['user_login'] : ('admin_' . $auth['user_id']);
        $decision = ($mode == 'risky_listings_approve') ? 'approve' : 'reject';

        $result = ($decision === 'approve')
            ? fn_is2or_ai_integration_review_approve($suggestion_id, $reviewed_by)
            : fn_is2or_ai_integration_review_reject($suggestion_id, $reviewed_by);

        if (!empty($result['success'])) {
            fn_is2or_ai_integration_resolve_by_suggestion($suggestion_id, $decision);
            fn_set_notification('N', __('notice'), __('is2or_ai_integration.review_saved'));
        } else {
            fn_set_notification('E', __('error'), __('is2or_ai_integration.review_error'));
        }
    }

    return [CONTROLLER_STATUS_REDIRECT, 'is2or_ai_integration.risky_listings'];
}

if ($mode == 'draft_reply') {
    header('Content-Type: application/json; charset=UTF-8');

    if (empty($auth['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('access_denied')]);
        exit;
    }

    $thread_id           = !empty($_REQUEST['thread_id']) ? (int) $_REQUEST['thread_id'] : 0;
    $communication_type  = !empty($_REQUEST['communication_type']) ? (string) $_REQUEST['communication_type'] : '';

    if (!$thread_id || !fn_vendor_communication_can_user_access_thread($thread_id, $auth)) {
        echo json_encode(['success' => false, 'error' => __('access_denied')]);
        exit;
    }

    $result = fn_is2or_ai_integration_call_support_reply($thread_id, $communication_type, $auth);

    if (empty($result['success']) || empty($result['data'])) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $output = !empty($result['data']['output']) ? $result['data']['output'] : [];

    if (!empty($output['needs_human'])) {
        echo json_encode(['success' => true, 'needs_human' => true]);
        exit;
    }

    $reply = (CART_LANGUAGE === 'ar' && !empty($output['reply_ar']))
        ? $output['reply_ar']
        : (!empty($output['reply_en']) ? $output['reply_en'] : '');

    echo json_encode([
        'success'     => true,
        'needs_human' => false,
        'reply'       => $reply,
    ]);
    exit;
}

// === Photo tidy-up: start job (vendor & admin, Update product page) =======
if ($mode == 'image_cleanup_start') {
    header('Content-Type: application/json; charset=UTF-8');

    $product_id = !empty($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;
    $pair_id    = !empty($_REQUEST['pair_id']) ? (int) $_REQUEST['pair_id'] : null;
    $image_url  = !empty($_REQUEST['image_url']) ? (string) $_REQUEST['image_url'] : '';

    if (!$product_id || !$image_url) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $result = fn_is2or_ai_integration_image_cleanup_start([
        'product_id' => $product_id,
        'pair_id'    => $pair_id,
        'url'        => $image_url,
    ]);

    if (empty($result['success']) || empty($result['data']['job_id'])) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    echo json_encode(['success' => true, 'job_id' => $result['data']['job_id']]);
    exit;
}

// === Photo tidy-up: poll job ===============================================
if ($mode == 'image_cleanup_poll') {
    header('Content-Type: application/json; charset=UTF-8');

    $job_id        = !empty($_REQUEST['job_id']) ? (string) $_REQUEST['job_id'] : '';
    $include_image = !empty($_REQUEST['include_image']);

    if (!$job_id) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $result = fn_is2or_ai_integration_image_cleanup_poll($job_id, $include_image);

    if (empty($result['success']) || empty($result['data'])) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    echo json_encode(['success' => true] + $result['data']);
    exit;
}

// === Photo tidy-up: "Use this" — apply cleaned photo + approve =============
if ($mode == 'image_cleanup_apply') {
    header('Content-Type: application/json; charset=UTF-8');

    $product_id     = !empty($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;
    $pair_id        = !empty($_REQUEST['pair_id']) ? (int) $_REQUEST['pair_id'] : null;
    $job_id         = !empty($_REQUEST['job_id']) ? (string) $_REQUEST['job_id'] : '';
    $suggestion_id  = !empty($_REQUEST['suggestion_id']) ? (int) $_REQUEST['suggestion_id'] : 0;

    if (!$product_id || !$job_id || !$suggestion_id) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    // Fetch the bytes now (job still in memory server-side)
    $poll = fn_is2or_ai_integration_image_cleanup_poll($job_id, true);

    if (empty($poll['success']) || empty($poll['data']['results'][0]['image_base64'])) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $row = $poll['data']['results'][0];

    $saved = fn_is2or_ai_integration_apply_cleaned_image($product_id, $pair_id, $row['image_base64'], $row['mime'], CART_LANGUAGE);

    if (!$saved) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $reviewed_by = !empty($auth['user_login']) ? $auth['user_login'] : ('admin_' . $auth['user_id']);
    fn_is2or_ai_integration_review_approve($suggestion_id, $reviewed_by);

    echo json_encode(['success' => true]);
    exit;
}

// === Photo tidy-up: "Keep original" — reject ================================
if ($mode == 'image_cleanup_reject') {
    header('Content-Type: application/json; charset=UTF-8');

    $suggestion_id = !empty($_REQUEST['suggestion_id']) ? (int) $_REQUEST['suggestion_id'] : 0;

    if (!$suggestion_id) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $reviewed_by = !empty($auth['user_login']) ? $auth['user_login'] : ('admin_' . $auth['user_id']);
    fn_is2or_ai_integration_review_reject($suggestion_id, $reviewed_by);

    echo json_encode(['success' => true]);
    exit;
}

// === Admin: Photo cleanup queue page =========================================
if ($mode == 'photo_cleanup_queue') {
    $result = fn_is2or_ai_integration_image_cleanup_queue('pending_review');

    $rows = (!empty($result['success']) && is_array($result['data'])) ? $result['data'] : [];

    foreach ($rows as &$row) {
        $product_id = !empty($row['output']['product_id']) ? (int) $row['output']['product_id'] : 0;
        $pair_id    = isset($row['output']['pair_id']) ? $row['output']['pair_id'] : null;

        $row['product_name']    = $product_id ? fn_get_product_name($product_id, DESCR_SL) : '';
        $row['product_id']      = $product_id;
        $row['pair_id']         = $pair_id;
        $row['can_apply']       = !empty($pair_id); // no pair_id -> cannot auto-save
    }
    unset($row);

    Tygh::$app['view']->assign('photo_cleanup_rows', $rows);
}

// === Admin: queue - preview a row (poll the job, needs image bytes) =========
if ($mode == 'photo_cleanup_preview') {
    header('Content-Type: application/json; charset=UTF-8');

    $job_id = !empty($_REQUEST['job_id']) ? (string) $_REQUEST['job_id'] : '';

    if (!$job_id) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $poll = fn_is2or_ai_integration_image_cleanup_poll($job_id, true);

    if (empty($poll['success']) || empty($poll['data']['results'][0]['image_base64'])) {
        // job likely expired from memory
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.tidy_expired')]);
        exit;
    }

    $row = $poll['data']['results'][0];

    echo json_encode([
        'success' => true,
        'mime'    => $row['mime'],
        'image'   => $row['image_base64'],
        'pair_id' => !empty($row['pair_id']) ? $row['pair_id'] : null,
    ]);
    exit;
}

// === Admin: queue - approve a row (apply cleaned image + approve) ==========
if ($mode == 'photo_cleanup_approve') {
    header('Content-Type: application/json; charset=UTF-8');

    $suggestion_id = !empty($_REQUEST['suggestion_id']) ? (int) $_REQUEST['suggestion_id'] : 0;
    $product_id    = !empty($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;
    $pair_id       = !empty($_REQUEST['pair_id']) ? (int) $_REQUEST['pair_id'] : 0;
    $job_id        = !empty($_REQUEST['job_id']) ? (string) $_REQUEST['job_id'] : '';

    if (!$suggestion_id || !$product_id || !$pair_id || !$job_id) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.tidy_no_pair')]);
        exit;
    }

    $poll = fn_is2or_ai_integration_image_cleanup_poll($job_id, true);

    if (empty($poll['success']) || empty($poll['data']['results'][0]['image_base64'])) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.tidy_expired')]);
        exit;
    }

    $row = $poll['data']['results'][0];

    $saved = fn_is2or_ai_integration_apply_cleaned_image($product_id, $pair_id, $row['image_base64'], $row['mime'], CART_LANGUAGE);

    if (!$saved) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $reviewed_by = !empty($auth['user_login']) ? $auth['user_login'] : ('admin_' . $auth['user_id']);
    fn_is2or_ai_integration_review_approve($suggestion_id, $reviewed_by);

    echo json_encode(['success' => true]);
    exit;
}

// === Admin: queue - reject a row ============================================
if ($mode == 'photo_cleanup_reject') {
    header('Content-Type: application/json; charset=UTF-8');

    $suggestion_id = !empty($_REQUEST['suggestion_id']) ? (int) $_REQUEST['suggestion_id'] : 0;

    if (!$suggestion_id) {
        echo json_encode(['success' => false, 'error' => __('is2or_ai_integration.error_bad_response')]);
        exit;
    }

    $reviewed_by = !empty($auth['user_login']) ? $auth['user_login'] : ('admin_' . $auth['user_id']);
    fn_is2or_ai_integration_review_reject($suggestion_id, $reviewed_by);

    echo json_encode(['success' => true]);
    exit;
}