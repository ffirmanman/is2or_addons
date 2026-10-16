<?php
defined('BOOTSTRAP') or die('Access denied');

if (empty($auth['user_type']) || $auth['user_type'] !== 'A') {
    return [CONTROLLER_STATUS_DENIED];
}

if ($mode !== 'reindex') {
    return [CONTROLLER_STATUS_NO_PAGE];
}

$offset = max(0, (int) ($_REQUEST['offset'] ?? 0));
$batch_size = max(1, min(500, (int) ($_REQUEST['batch_size'] ?? 100)));
$reindex_result = fn_is2or_elastic_search_reindex_products($offset, $batch_size, $auth);

$message = sprintf(
    'Elasticsearch reindex batch done. Synced %d/%d products in this batch (offset %d). Total active products: %d.',
    (int) $reindex_result['synced_products'],
    (int) $reindex_result['processed_in_batch'],
    (int) $offset,
    (int) $reindex_result['total_active_products']
);
fn_set_notification('N', __('notice'), $message);

if (!empty($reindex_result['has_more'])) {
    $next_message = sprintf(
        'Next batch is available. Continue with dispatch=is2or_elastic_search.reindex&offset=%d&batch_size=%d',
        (int) $reindex_result['next_offset'],
        (int) $batch_size
    );
    fn_set_notification('W', __('warning'), $next_message);
} else {
    fn_set_notification('N', __('notice'), 'Elasticsearch reindex completed for all active products.');
}

return [CONTROLLER_STATUS_REDIRECT, 'addons.update?addon=is2or_elastic_search'];
