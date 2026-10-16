<?php

defined('BOOTSTRAP') or die('Access denied');
define('IS2OR_DOCUMENT_TOKEN_TTL', 900);
if (!defined('IS2OR_BLOCK_STATUS')) {
    define('IS2OR_BLOCK_STATUS', 'D');
}

use Tygh\Registry;

/**
 *
 * @var array<string, int[]>
 */
$GLOBALS['is2or_ai_integration_order_cache'] = [];

/**
 * Hook: get_products_pre
 *
 *
 * @param array $params        CS-Cart product search params (by reference).
 * @param int   $items_per_page
 * @param string $lang_code
 */
function fn_is2or_ai_integration_get_products_pre(&$params, $items_per_page = 0, $lang_code = '')
{
    if (!isset($params['q']) || !is_string($params['q'])) {
        return;
    }
    $query = trim($params['q']);
    if ($query === '') {
        return;
    }

    $has_advanced_filters = !empty($params['feature'])
        || !empty($params['features_hash']);

    if ($has_advanced_filters) {
        return;
    }

    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    // Silently skip if the addon is not configured yet
    if ($api_base_url === '' || $api_token === '') {
        return;
    }

    $limit = (int) Registry::get('addons.is2or_ai_integration.search_limit');
    if ($limit <= 0) {
        $limit = 10;
    }

    $product_ids = fn_is2or_ai_integration_call_search($query, $limit, $api_base_url, $api_token);

    if (empty($product_ids)) {
        // API failed, returned fallback_used=true, or returned no results — let CS-Cart handle it
        return;
    }

    unset(
        $params['q'],
        $params['pname'],
        $params['search_query'],
        $params['pkeywords'],
        $params['pshort'],
        $params['pfull']
    );

    // Restrict the result set to the AI-ranked product IDs.
    $params['pid'] = $product_ids;

    $cache_key = fn_is2or_ai_integration_cache_key($product_ids);
    $GLOBALS['is2or_ai_integration_order_cache'][$cache_key] = $product_ids;

    $params['is2or_ai_order_key'] = $cache_key;
}

/**
 * Hook: get_products_post
 *
 * @param array $products  Fetched products, keyed by numeric index or product_id
 *                          (by reference).
 * @param array $params    The same/derived $params array used for the query.
 */
function fn_is2or_ai_integration_get_products_post(&$products, $params)
{
    if (empty($products) || !is_array($products)) {
        return;
    }

    $cache_key = $params['is2or_ai_order_key'] ?? null;
    if ($cache_key === null || empty($GLOBALS['is2or_ai_integration_order_cache'][$cache_key])) {
        return;
    }

    $order = $GLOBALS['is2or_ai_integration_order_cache'][$cache_key];
    unset($GLOBALS['is2or_ai_integration_order_cache'][$cache_key]);

    // Index existing products by product_id for quick lookup
    $by_id = [];
    foreach ($products as $product) {
        if (!empty($product['product_id'])) {
            $by_id[(int) $product['product_id']] = $product;
        }
    }

    if (empty($by_id)) {
        return;
    }

    $sorted = [];
    foreach ($order as $pid) {
        if (isset($by_id[$pid])) {
            $sorted[] = $by_id[$pid];
            unset($by_id[$pid]);
        }
    }

    foreach ($by_id as $leftover) {
        $sorted[] = $leftover;
    }

    $products = $sorted;
}

/**
 * Calls POST /search on the IS2OR AI service.
 *
 * @param string $query
 * @param int    $limit
 * @param string $api_base_url  Full base URL, e.g. https://ai.example.com
 * @param string $api_token     Bearer token
 *
 * @return int[]
 */
function fn_is2or_ai_integration_call_search($query, $limit, $api_base_url, $api_token)
{
    $endpoint = rtrim($api_base_url, '/') . '/search';

    $payload = [
        'query' => $query,
        'limit' => $limit,
    ];

    $storefront_id = Registry::get('runtime.storefront_id');
    if (!empty($storefront_id)) {
        $payload['filters']['storefront_id'] = (int) $storefront_id;
    }

    $body = json_encode($payload);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 5, // hard 5-second timeout so storefront never hangs
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_token,
        ],
    ]);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    // Any network error or non-200 response → fallback
    if ($curl_err !== '' || $http_code !== 200 || $raw === false) {
        return [];
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return [];
    }

    // API signals no usable result — caller should use native search
    if (!empty($data['fallback_used'])) {
        return [];
    }

    if (empty($data['results']) || !is_array($data['results'])) {
        return [];
    }

    // Extract ordered product IDs from the ranked results
    $product_ids = [];
    foreach ($data['results'] as $hit) {
        if (!empty($hit['product_id'])) {
            $product_ids[] = (int) $hit['product_id'];
        }
    }

    return $product_ids;
}

/**
 * Builds a stable cache key for a given ordered list of product IDs.
 *
 * @param int[] $product_ids
 * @return string
 */
function fn_is2or_ai_integration_cache_key(array $product_ids)
{
    return md5(implode(',', $product_ids));
}

/**
 * Calls POST /features/listing on the IS2OR AI service to generate
 * AI-assisted product listing content (title/description/features)
 * from a product name and optional extra hints.
 *
 * @param string   $name        Product name (required by the AI service).
 * @param string   $brand       Optional brand name.
 * @param string   $notes       Optional free-text notes/hints.
 * @param string[] $features    Optional list of feature strings.
 * @param int      $product_id  Optional existing product id (omitted for new/unsaved products).
 *
 * @return array
 */
function fn_is2or_ai_integration_call_listing($name, $brand = '', $notes = '', array $features = [], $product_id = 0)
{
    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    if ($api_base_url === '' || $api_token === '') {
        return [
            'success' => false,
            'output'  => null,
            'error'   => __('is2or_ai_integration.error_not_configured'),
        ];
    }

    $name = trim((string) $name);
    if ($name === '') {
        return [
            'success' => false,
            'output'  => null,
            'error'   => __('is2or_ai_integration.error_name_required'),
        ];
    }

    $timeout = (int) Registry::get('addons.is2or_ai_integration.listing_timeout');
    if ($timeout <= 0) {
        $timeout = 20;
    }

    $payload = [
        'name' => $name,
    ];

    $brand = trim((string) $brand);
    if ($brand !== '') {
        $payload['brand'] = $brand;
    }

    $notes = trim((string) $notes);
    if ($notes !== '') {
        $payload['notes'] = $notes;
    }

    $features = array_values(array_filter(array_map('trim', $features), function ($v) {
        return $v !== '';
    }));
    if (!empty($features)) {
        $payload['features'] = $features;
    }

    $product_id = (int) $product_id;
    if ($product_id > 0) {
        $payload['product_id'] = $product_id;
    }

    $endpoint = rtrim($api_base_url, '/') . '/features/listing';
    $body     = json_encode($payload);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_token,
        ],
    ]);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    $debug_mode = Registry::get('addons.is2or_ai_integration.debug_mode') === 'Y';
    if ($debug_mode) {
        fn_log_event('is2or_ai_integration', 'generate_listing', [
            'endpoint'  => $endpoint,
            'payload'   => $payload,
            'http_code' => $http_code,
            'curl_err'  => $curl_err,
            'response'  => $raw,
        ]);
    }

    if ($curl_err !== '') {
        return [
            'success' => false,
            'output'  => null,
            'error'   => __('is2or_ai_integration.error_connection'),
        ];
    }

    if ($raw === false || $http_code < 200 || $http_code >= 300) {
        return [
            'success' => false,
            'output'  => null,
            'error'   => __('is2or_ai_integration.error_bad_response') . ' (HTTP ' . $http_code . ')',
        ];
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return [
            'success' => false,
            'output'  => null,
            'error'   => __('is2or_ai_integration.error_bad_response') . ' (HTTP ' . $http_code . ')',
        ];
    }

    if (!empty($data['error'])) {
        return [
            'success' => false,
            'output'  => null,
            'error'   => is_string($data['error']) ? $data['error'] : __('is2or_ai_integration.error_bad_response'),
        ];
    }

    if (empty($data['output']) || !is_array($data['output'])) {
        return [
            'success' => false,
            'output'  => null,
            'error'   => __('is2or_ai_integration.error_empty_output'),
        ];
    }

    return [
        'success'         => true,
        'output'          => $data['output'],
        'suggestion_id'   => $data['suggestion_id'] ?? null,
        'review_required' => !empty($data['review_required']),
        'error'           => null,
    ];
}

/**
 * Hook: update_product_post
 *
 * Fired by CS-Cart after a product is inserted OR updated (fn_update_product).
 * call to the AI service so the storefront
 * request is never delayed waiting for the AI service to respond.
 *
 * @param array  $product_data           Product data array (by reference).
 * @param int    $product_id             The product id (new or existing).
 * @param string $lang_code
 * @param bool   $update_original_product
 */
function fn_is2or_ai_integration_update_product_post(&$product_data, $product_id, $lang_code = '', $update_original_product = true)
{
    fn_is2or_ai_integration_reindex_product((int) $product_id);

    if (!empty($product_data['company_id'])) {
        fn_is2or_ai_integration_call_moderate((int) $product_id);
    }
}

/**
 * Hook: delete_product_post
 *
 * Fired by CS-Cart after a product has been deleted (fn_delete_product).
 *  DELETE /index/{product_id} call to the
 * AI service so it can drop the product from its index.
 *
 * @param int $product_id
 * @param int $company_id
 */
function fn_is2or_ai_integration_delete_product_post($product_id, $company_id = 0)
{
    fn_is2or_ai_integration_delete_index($product_id);
}

/**
 * Fires a fire-and-forget POST /index request for a single product
 * (insert or update — the AI service decides whether to re-embed).
 *
 * This does NOT wait for (or read) the AI service's response.
 *
 * @param int $product_id
 * @return void
 */
function fn_is2or_ai_integration_reindex_product($product_id)
{
    $product_id = (int) $product_id;
    if ($product_id <= 0) {
        return;
    }

    fn_is2or_ai_integration_fire_request(
        'POST',
        '/index',
        json_encode(['product_id' => $product_id]),
        'reindex_product',
        $product_id
    );
}

/**
 * Fires a fire-and-forget DELETE /index/{product_id} request to remove a
 * product from the AI service's index.
 *
 * This does NOT wait for (or read) the AI service's response.
 *
 * @param int $product_id
 * @return void
 */
function fn_is2or_ai_integration_delete_index($product_id)
{
    $product_id = (int) $product_id;
    if ($product_id <= 0) {
        return;
    }

    fn_is2or_ai_integration_fire_request(
        'DELETE',
        '/index/' . $product_id,
        null,
        'delete_index',
        $product_id
    );
}

/**
 * Low-level helper: opens a raw socket to the AI service, writes an HTTP
 * request, and closes the connection immediately without reading the
 * response. This guarantees the storefront/admin request is never blocked
 * waiting on the AI service.
 *
 * @param string      $method     HTTP method, e.g. 'POST' or 'DELETE'.
 * @param string      $path       Path (relative to the configured base URL),
 *                                e.g. '/index' or '/index/12345'.
 * @param string|null $body       Raw request body, or null for no body.
 * @param string      $log_action Action name used for debug_mode logging.
 * @param int         $product_id Product id, used only for logging.
 *
 * @return void
 */
function fn_is2or_ai_integration_fire_request($method, $path, $body, $log_action, $product_id)
{
    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    // Silently skip if the addon is not configured yet
    if ($api_base_url === '' || $api_token === '') {
        return;
    }

    $endpoint = rtrim($api_base_url, '/') . $path;

    // Reuse the listing timeout setting so admins have a single knob for
    // "how long may we wait on the AI service" across features.
    $timeout = (int) Registry::get('addons.is2or_ai_integration.listing_timeout');
    if ($timeout <= 0) {
        $timeout = 20;
    }

    $headers = [
        'Authorization: Bearer ' . $api_token,
    ];

    $curl_opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => $timeout,
    ];

    if ($body !== null) {
        $headers[]                       = 'Content-Type: application/json';
        $curl_opts[CURLOPT_POSTFIELDS]    = $body;
    }

    $curl_opts[CURLOPT_HTTPHEADER] = $headers;

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, $curl_opts);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    $success = ($curl_err === '' && $raw !== false && $http_code >= 200 && $http_code < 300);

    $debug_mode = Registry::get('addons.is2or_ai_integration.debug_mode') === 'Y';
    if ($debug_mode) {
        fn_log_event('is2or_ai_integration', $log_action, [
            'product_id' => $product_id,
            'endpoint'   => $endpoint,
            'http_code'  => $http_code,
            'curl_err'   => $curl_err,
            'response'   => $raw,
        ]);
    }
}



/**
 * Calls POST /features/categorize on the IS2OR AI service to get
 * category suggestions for a product.
 *
 * @param string $name        Product name (required).
 * @param string $description Optional description/notes used as context.
 * @param int    $product_id  Optional existing product id.
 *
 * @return array
 */
function fn_is2or_ai_integration_call_categorize($name, $description = '', $product_id = 0)
{
    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    if ($api_base_url === '' || $api_token === '') {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_not_configured')];
    }

    $name = trim((string) $name);
    if ($name === '') {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_name_required')];
    }

    $timeout = (int) Registry::get('addons.is2or_ai_integration.listing_timeout');
    if ($timeout <= 0) {
        $timeout = 20;
    }

    $payload = ['name' => $name];

    $description = trim((string) $description);
    if ($description !== '') {
        $payload['description'] = $description;
    }

    $product_id = (int) $product_id;
    if ($product_id > 0) {
        $payload['product_id'] = $product_id;
    }

    $endpoint = rtrim($api_base_url, '/') . '/features/categorize';
    $body     = json_encode($payload);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_token,
        ],
    ]);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if (Registry::get('addons.is2or_ai_integration.debug_mode') === 'Y') {
        fn_log_event('is2or_ai_integration', 'generate_categorize', [
            'endpoint' => $endpoint, 'payload' => $payload,
            'http_code' => $http_code, 'curl_err' => $curl_err, 'response' => $raw,
        ]);
    }

    if ($curl_err !== '') {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_connection')];
    }
    if ($raw === false || $http_code < 200 || $http_code >= 300) {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_bad_response') . ' (HTTP ' . $http_code . ')'];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_bad_response')];
    }
    if (!empty($data['error'])) {
        return ['success' => false, 'output' => null, 'error' => is_string($data['error']) ? $data['error'] : __('is2or_ai_integration.error_bad_response')];
    }
    if (empty($data['output']) || !is_array($data['output'])) {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_empty_output')];
    }

    return [
        'success'         => true,
        'output'          => $data['output'],
        'suggestion_id'   => $data['suggestion_id'] ?? null,
        'review_required' => !empty($data['review_required']),
        'error'           => null,
    ];
}

/**
 * Calls POST /features/attributes on the IS2OR AI service to generate
 * AI-suggested product attributes based on name/brand/description/notes.
 *
 * @param string $name        Product name (required).
 * @param int    $product_id  Optional existing product id.
 * @param string $brand       Optional brand name.
 * @param string $description Optional product description.
 * @param string $notes       Optional free-form vendor notes.
 *
 * @return array
 */
function fn_is2or_ai_integration_call_attributes($name, $product_id = 0, $brand = '', $description = '', $notes = '')
{
    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    if ($api_base_url === '' || $api_token === '') {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_not_configured')];
    }

    $name = trim((string) $name);
    if ($name === '') {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_name_required')];
    }

    $timeout = (int) Registry::get('addons.is2or_ai_integration.listing_timeout');
    if ($timeout <= 0) {
        $timeout = 20;
    }

    $payload = ['name' => $name];

    $product_id = (int) $product_id;
    if ($product_id > 0) {
        $payload['product_id'] = $product_id;
    }

    $brand = trim((string) $brand);
    if ($brand !== '') {
        $payload['brand'] = $brand;
    }

    $description = trim((string) $description);
    if ($description !== '') {
        $payload['description'] = $description;
    }

    $notes = trim((string) $notes);
    if ($notes !== '') {
        $payload['notes'] = $notes;
    }

    $endpoint = rtrim($api_base_url, '/') . '/features/attributes';
    $body     = json_encode($payload);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_token,
        ],
    ]);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if (Registry::get('addons.is2or_ai_integration.debug_mode') === 'Y') {
        fn_log_event('is2or_ai_integration', 'generate_attributes', [
            'endpoint'  => $endpoint,
            'payload'   => $payload,
            'http_code' => $http_code,
            'curl_err'  => $curl_err,
            'response'  => $raw,
        ]);
    }

    if ($curl_err !== '') {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_connection')];
    }

    if ($raw === false || $http_code < 200 || $http_code >= 300) {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_bad_response') . ' (HTTP ' . $http_code . ')'];
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_bad_response')];
    }

    if (!empty($data['error'])) {
        return ['success' => false, 'output' => null, 'error' => is_string($data['error']) ? $data['error'] : __('is2or_ai_integration.error_bad_response')];
    }

    if (empty($data['output']) || !is_array($data['output'])) {
        return ['success' => false, 'output' => null, 'error' => __('is2or_ai_integration.error_empty_output')];
    }

    return [
        'success'         => true,
        'output'          => $data['output'],
        'suggestion_id'   => $data['suggestion_id'] ?? null,
        'review_required' => !empty($data['review_required']),
        'error'           => null,
    ];
}

function fn_is2or_ai_integration_similar_product_view($product_id, $product_data)
{
    if (empty($product_id)) {
        return [];
    }

    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    if ($api_base_url === '' || $api_token === '') {
        return [];
    }

    $limit = 20;
    $endpoint = rtrim($api_base_url, '/') . '/recommend/similar/' . (int)$product_id . '?limit=' . $limit;

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Bearer ' . $api_token,
        ],
    ]);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    // Any network error or non-200 response → fallback return empty array
    if ($curl_err !== '' || $http_code !== 200 || $raw === false) {
        return [];
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return [];
    }

    if (empty($data['results']) || !is_array($data['results'])) {
        return [];
    }

    $product_ids = [];
    foreach ($data['results'] as $hit) {
        if (!empty($hit['product_id'])) {
            $product_ids[] = (int) $hit['product_id'];
        }
    }
    
    return $product_ids;
}

function fn_is2or_ai_integration_also_bought_products_view($product_id, $product_data)
{
    if (empty($product_id)) {
        return [];
    }

    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    if ($api_base_url === '' || $api_token === '') {
        return [];
    }

    $limit = 20;
    $endpoint = rtrim($api_base_url, '/') . '/recommend/also-bought/' . (int)$product_id . '?limit=' . $limit;

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Bearer ' . $api_token,
        ],
    ]);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    // Any network error or non-200 response → fallback return empty array
    if ($curl_err !== '' || $http_code !== 200 || $raw === false) {
        return [];
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return [];
    }

    if (empty($data['results']) || !is_array($data['results'])) {
        return [];
    }

    $product_ids = [];
    foreach ($data['results'] as $hit) {
        if (!empty($hit['product_id'])) {
            $product_ids[] = (int) $hit['product_id'];
        }
    }
    
    return $product_ids;
}


function fn_is2or_ai_integration_get_products_by_ids($product_ids)
{
    if (empty($product_ids)) {
        return [];
    }

    $params = [
        'pid'            => implode(',', array_map('intval', $product_ids)),
        'items_per_page' => 0,
    ];

    list($products) = fn_get_products($params, Tygh::$app['session']['auth']);

    if (empty($products)) {
        return [];
    }

    $products_by_id = [];
    foreach ($products as $p) {
        $products_by_id[$p['product_id']] = $p;
    }

    $ordered = [];
    foreach ($product_ids as $pid) {
        if (isset($products_by_id[$pid])) {
            $ordered[] = $products_by_id[$pid];
        }
    }

    fn_gather_additional_products_data($ordered, [
        'get_icon'         => true,
        'get_detailed'     => true,
        'get_prices'       => true,
        'get_taxed_prices' => true,
    ]);

    return $ordered;
}

/**
 * Calls /recommend/for-customer/{user_id} and returns a list of product IDs.
 * Mirrors fn_is2or_ai_integration_similar_product_view() but for the
 * personalized "for customer" endpoint.
 */
function fn_is2or_ai_integration_for_customer_products_view($user_id)
{
    if (empty($user_id)) {
        return [];
    }

    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    if ($api_base_url === '' || $api_token === '') {
        return [];
    }

    $limit    = 20;
    $endpoint = rtrim($api_base_url, '/') . '/recommend/for-customer/' . (int) $user_id . '?limit=' . $limit;

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Bearer ' . $api_token,
        ],
    ]);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err !== '' || $http_code !== 200 || $raw === false) {
        return [];
    }

    $data = json_decode($raw, true);

    if (!is_array($data) || empty($data['results']) || !is_array($data['results'])) {
        return [];
    }

    $product_ids = [];
    foreach ($data['results'] as $hit) {
        if (!empty($hit['product_id'])) {
            $product_ids[] = (int) $hit['product_id'];
        }
    }

    return $product_ids;
}

/**
 * Content-provider function for the "is2or_for_customer_products" block type.
 * Registered in schemas/block_manager/blocks.post.php.
 *
 * Returns [] (→ block renders nothing) when nobody is logged in.
 */
function fn_is2or_ai_integration_get_for_customer_products_block()
{
    $auth = Tygh::$app['session']['auth'];

    if (empty($auth['user_id'])) {
        return [];
    }

    $product_ids = fn_is2or_ai_integration_for_customer_products_view((int) $auth['user_id']);

    if (empty($product_ids)) {
        return [];
    }

    // Reuses the same helper already used by similar_products / also_bought.
    return fn_is2or_ai_integration_get_products_by_ids($product_ids);
}

function fn_is2or_ai_integration_engine_request($method, $path, $query = [], $body = null)
{
    $api_base_url = trim((string) Registry::get('addons.is2or_ai_integration.api_base_url'));
    $api_token    = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    if ($api_base_url === '' || $api_token === '') {
        return ['success' => false, 'error' => 'Missing API config', 'data' => null];
    }

    $url = rtrim($api_base_url, '/') . $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $api_token,
    ];

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 10,
    ];

    if (strtoupper($method) === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
            $headers[] = 'Content-Type: application/json';
        }
    }

    $opts[CURLOPT_HTTPHEADER] = $headers;

    $ch = curl_init($url);
    curl_setopt_array($ch, $opts);

    $raw       = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err !== '' || $raw === false) {
        return ['success' => false, 'error' => $curl_err ?: 'Network error', 'data' => null];
    }

    $data = json_decode($raw, true);

    if ($http_code < 200 || $http_code >= 300) {
        return ['success' => false, 'error' => 'HTTP ' . $http_code, 'data' => $data];
    }

    return ['success' => true, 'error' => null, 'data' => $data];
}

function fn_is2or_ai_integration_get_review_queue($feature, $status = 'pending_review', $limit = 50)
{
    $result = fn_is2or_ai_integration_engine_request('GET', '/engine/review', [
        'feature' => $feature,
        'status'  => $status,
        'limit'   => $limit,
    ]);

    if (empty($result['success']) || !is_array($result['data'])) {
        return [];
    }

    return $result['data'];
}

function fn_is2or_ai_integration_review_get($suggestion_id)
{
    return fn_is2or_ai_integration_engine_request('GET', '/engine/review/' . (int) $suggestion_id);
}

function fn_is2or_ai_integration_review_approve($suggestion_id, $reviewed_by, $edited_output = null)
{
    $body = ['reviewed_by' => $reviewed_by];
    if ($edited_output !== null) {
        $body['edited_output'] = $edited_output;
    }

    return fn_is2or_ai_integration_engine_request('POST', '/engine/review/' . (int) $suggestion_id . '/approve', [], $body);
}

function fn_is2or_ai_integration_review_reject($suggestion_id, $reviewed_by)
{
    return fn_is2or_ai_integration_engine_request('POST', '/engine/review/' . (int) $suggestion_id . '/reject', [], [
        'reviewed_by' => $reviewed_by,
    ]);
}

function fn_is2or_ai_integration_call_moderate($product_id)
{
    if (empty($product_id)) {
        return;
    }

    $product = fn_get_product_data(
        $product_id,
        Tygh::$app['session']['auth'],
        CART_LANGUAGE,
        '',
        true,
        true,
        false,
        true
    );

    if (empty($product)) {
        return;
    }

    $description = !empty($product['full_description'])
        ? $product['full_description']
        : (!empty($product['short_description']) ? $product['short_description'] : '');

    $body = [
        'name'          => !empty($product['product']) ? $product['product'] : '',
        'product_id'    => (int) $product_id,
        'description'   => strip_tags((string) $description),
        'price'         => !empty($product['price']) ? (float) $product['price'] : 0,
        'seller_id'     => !empty($product['company_id']) ? (int) $product['company_id'] : 0,
        'storefront_id' => 1,
    ];

    $result = fn_is2or_ai_integration_engine_request('POST', '/features/moderate', [], $body);

    if (empty($result['success']) || empty($result['data'])) {
        return;
    }

    fn_is2or_ai_integration_apply_moderation_result((int) $product_id, $result['data']);
}

function fn_is2or_ai_integration_apply_moderation_result($product_id, $data)
{
    $output  = !empty($data['output']) ? $data['output'] : [];
    $risk    = !empty($output['risk']) ? $output['risk'] : '';
    $action  = !empty($output['action']) ? $output['action'] : '';
    $reasons = !empty($output['reasons']) ? $output['reasons'] : [];

    $detail_en = [];
    $detail_ar = [];
    foreach ($reasons as $reason) {
        if (!empty($reason['detail'])) {
            $detail_en[] = $reason['detail'];
        }
        if (!empty($reason['detail_ar'])) {
            $detail_ar[] = $reason['detail_ar'];
        }
    }

    $current = db_get_row('SELECT status FROM ?:products WHERE product_id = ?i', $product_id);
    $prev_status = !empty($current['status']) ? $current['status'] : 'A';

    $applied_status = $prev_status;

    // Never delete. Only "block" changes status; "review" and "allow" just get logged.
    if ($action === 'block' && $prev_status !== IS2OR_BLOCK_STATUS) {
        $applied_status = IS2OR_BLOCK_STATUS;
        db_query('UPDATE ?:products SET status = ?s WHERE product_id = ?i', $applied_status, $product_id);
    }

    db_query(
        'REPLACE INTO ?:is2or_risky_products
            (product_id, suggestion_id, risk, action, detail_en, detail_ar, prev_status, applied_status, created_at, resolved_at)
         VALUES (?i, ?i, ?s, ?s, ?s, ?s, ?s, ?s, ?i, ?i)',
        $product_id,
        !empty($data['suggestion_id']) ? (int) $data['suggestion_id'] : 0,
        $risk,
        $action,
        implode(' | ', $detail_en),
        implode(' | ', $detail_ar),
        $prev_status,
        $applied_status,
        TIME,
        0
    );
}

function fn_is2or_ai_integration_resolve_by_suggestion($suggestion_id, $decision)
{
    $row = db_get_row(
        'SELECT * FROM ?:is2or_risky_products WHERE suggestion_id = ?i',
        (int) $suggestion_id
    );

    if (empty($row)) {
        return;
    }

    if ($decision === 'reject' && $row['applied_status'] === IS2OR_BLOCK_STATUS) {
        db_query(
            'UPDATE ?:products SET status = ?s WHERE product_id = ?i',
            $row['prev_status'],
            $row['product_id']
        );
    }

    db_query(
        'UPDATE ?:is2or_risky_products SET resolved_at = ?i WHERE product_id = ?i',
        TIME,
        $row['product_id']
    );
}

function fn_is2or_ai_integration_get_product_flag($product_id)
{
    if (empty($product_id)) {
        return null;
    }

    $row = db_get_row(
        'SELECT * FROM ?:is2or_risky_products WHERE product_id = ?i AND resolved_at = ?i',
        (int) $product_id,
        0
    );

    return !empty($row) ? $row : null;
}

function fn_is2or_ai_integration_get_product_data_post(&$product_data, $auth, $preview, $lang_code)
{
    if (empty($product_data['product_id'])) {
        return;
    }

    $flag = fn_is2or_ai_integration_get_product_flag($product_data['product_id']);

    if (empty($flag)) {
        return;
    }

    $detail = ($lang_code === 'ar' && !empty($flag['detail_ar']))
        ? $flag['detail_ar']
        : $flag['detail_en'];

    Tygh::$app['view']->assign('is2or_risky_flag', [
        'risk'   => $flag['risk'],
        'action' => $flag['action'],
        'detail' => $detail,
    ]);
}

function fn_is2or_ai_integration_call_chat($message, $session_id, $locale)
{
    $body = [
        'locale'     => $locale,
        'message'    => $message,
        'searched'   => true,
        'session_id' => (string) $session_id,
    ];

    return fn_is2or_ai_integration_engine_request('POST', '/chat', [], $body);
}

function fn_is2or_ai_integration_call_support_reply($thread_id, $communication_type, $auth)
{
    fn_load_addon('vendor_communication');

    $thread_data = db_get_row(
        'SELECT thread_id, user_id, company_id, object_type, object_id, last_message_user_id
         FROM ?:vendor_communications
         WHERE thread_id = ?i',
        (int) $thread_id
    );

    if (empty($thread_data)) {
        return ['success' => false, 'error' => 'Thread not found', 'data' => null];
    }

    $params = [
        'thread_id'          => (int) $thread_id,
        'user_id'            => (int) $auth['user_id'],
        'communication_type' => $communication_type,
        'items_per_page'     => 500,
        'get_company_data'   => true,
    ];

    $messages = fn_vendor_communication_get_thread_messages($params);

    $role_map = [
        'C' => 'customer',
        'V' => 'vendor',
        'A' => 'admin',
    ];

    $chat_messages = [];
    foreach ($messages as $m) {
        $role = !empty($role_map[$m['user_type']]) ? $role_map[$m['user_type']] : 'customer';
        $chat_messages[] = [
            'role' => $role,
            'text' => strip_tags((string) $m['message']),
        ];
    }

    $order_id = null;
    if (!empty($thread_data['object_type']) && $thread_data['object_type'] === VC_OBJECT_TYPE_ORDER && !empty($thread_data['object_id'])) {
        $order_id = (int) $thread_data['object_id'];
    }

    $body = [
        'order'     => null,
        'locale'    => CART_LANGUAGE,
        'user_id'   => !empty($thread_data['user_id']) ? (int) $thread_data['user_id'] : null,
        'messages'  => $chat_messages,
        'order_id'  => $order_id,
        'ticket_id' => (int) $thread_id,
    ];

    return fn_is2or_ai_integration_engine_request('POST', '/features/support-reply', [], $body);
}

// === Photo tidy-up ==========================================================

/**
 * Start a cleanup job for one image.
 * $image = ['product_id' => int, 'pair_id' => int|null, 'url' => string]
 */
function fn_is2or_ai_integration_image_cleanup_start($image)
{
    $body = [
        'images' => [
            [
                'product_id' => (int) $image['product_id'],
                'pair_id'    => !empty($image['pair_id']) ? (int) $image['pair_id'] : null,
                'url'        => (string) $image['url'],
            ],
        ],
        // operations & target left as default (all four / 1000x1000 / #ffffff)
        'review_required' => true,
    ];
    
    return fn_is2or_ai_integration_engine_request('POST', '/features/image/cleanup', [], $body);
}

/**
 * Poll a cleanup job.
 * $include_image = true only when status is already "done" and you need the bytes.
 */
function fn_is2or_ai_integration_image_cleanup_poll($job_id, $include_image = false)
{
    $query = $include_image ? ['include_image' => 'true'] : [];

    return fn_is2or_ai_integration_engine_request('GET', '/features/image/cleanup/' . $job_id, $query, []);
}

/**
 * List pending photo-cleanup suggestions for the admin queue page.
 */
function fn_is2or_ai_integration_image_cleanup_queue($status = 'pending_review')
{
    $query = [
        'feature' => 'image_cleanup',
        'status'  => $status,
    ];

    return fn_is2or_ai_integration_engine_request('GET', '/engine/review', $query, []);
}

/**
 * Save the cleaned JPEG (base64) as the product's image pair, the same way
 * CS-Cart saves any manually uploaded product photo (fn_update_image_pairs).
 *
 * @param int    $product_id
 * @param int    $pair_id      Existing pair_id to update (from the review row).
 * @param string $image_base64
 * @param string $mime
 * @param string $lang_code
 * @return bool
 */
function fn_is2or_ai_integration_apply_cleaned_image($product_id, $pair_id, $image_base64, $mime, $lang_code = CART_LANGUAGE)
{
    $product_id = (int) $product_id;
    $pair_id    = (int) $pair_id;

    if (!$product_id || !$pair_id) {
        return false;
    }

    $binary = base64_decode($image_base64, true);
    if ($binary === false || $binary === '') {
        return false;
    }

    $ext = ($mime === 'image/png') ? 'png' : 'jpg';

    $tmp_path = tempnam(sys_get_temp_dir(), 'is2or_tidy_');
    if ($tmp_path === false) {
        return false;
    }

    // give it a real extension - some CS-Cart image helpers rely on it
    $tmp_path_ext = $tmp_path . '.' . $ext;
    rename($tmp_path, $tmp_path_ext);
    $tmp_path = $tmp_path_ext;

    file_put_contents($tmp_path, $binary);

    if (!fn_get_image_size($tmp_path)) {
        // not a real/valid image after decode - bail out, keep original file untouched
        @unlink($tmp_path);
        return false;
    }

    $file_data = [
        'name' => 'tidy_' . $product_id . '_' . TIME . '.' . $ext,
        'type' => $mime,
        'path' => $tmp_path,
        'size' => filesize($tmp_path),
    ];

    $pairs_data = [
        0 => [
            'pair_id' => $pair_id,
            // 'type' intentionally omitted: fn_update_image_pairs only overwrites
            // the pair 'type' (M/A/N) when it's explicitly set, so the existing
            // main/additional flag is preserved automatically.
        ],
    ];

    // Same file used for icon + detailed, same as a normal manual upload.
    $result_pair_ids = fn_update_image_pairs(
        [0 => $file_data], // icons
        [0 => $file_data], // detailed
        $pairs_data,
        $product_id,
        'product',
        [],
        true,
        $lang_code
    );

    @unlink($tmp_path);

    return !empty($result_pair_ids);
}

/**
 * Build a short-lived signed URL the AI engine can fetch the vendor's document from.
 */
function fn_is2or_ai_integration_get_signed_document_url($company_id, $field_id, $filename)
{
    $secret = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));

    if ($secret === '') {
        return '';
    }

    $ttl = (int) Registry::ifGet('addons.is2or_ai_integration.listing_timeout', 1800);
    $ttl = $ttl > 0 ? $ttl : 1800;

    $expires = TIME + $ttl;
    $token   = fn_is2or_ai_integration_generate_document_token($company_id, $field_id, $filename, $expires, $secret);

    return fn_url(
        'is2or_ai_integration.get_document'
        . '?company_id=' . (int) $company_id
        . '&field_id=' . (int) $field_id
        . '&filename=' . urlencode($filename)
        . '&expires=' . $expires
        . '&token=' . $token,
        \Tygh\Enum\SiteArea::STOREFRONT,
        'https'
    );
}

function fn_is2or_ai_integration_generate_document_token($company_id, $field_id, $filename, $expires, $secret = null)
{
    if ($secret === null) {
        $secret = trim((string) Registry::get('addons.is2or_ai_integration.api_token'));
    }

    $payload = $company_id . '|' . $field_id . '|' . $filename . '|' . $expires;

    return hash_hmac('sha256', $payload, $secret);
}

function fn_is2or_ai_integration_verify_document_token($company_id, $field_id, $filename, $expires, $token)
{
    if (empty($token) || empty($expires) || (int) $expires < TIME) {
        return false;
    }

    $expected_token = fn_is2or_ai_integration_generate_document_token($company_id, $field_id, $filename, $expires);

    return hash_equals($expected_token, (string) $token);
}

function fn_is2or_ai_integration_call_verify_document($document_type, $url, $company_id = null, $expected = [], $user_id = null, $locale = null)
{
    $body = [
        'document_type'   => $document_type,
        'url'             => $url,
        'review_required' => true,
    ];

    if (!empty($company_id)) {
        $body['company_id'] = (int) $company_id;
    }
    if (!empty($user_id)) {
        $body['user_id'] = (int) $user_id;
    }
    if (!empty($locale)) {
        $body['locale'] = $locale;
    }
    if (!empty($expected)) {
        $body['expected'] = array_filter($expected, function ($v) {
            return $v !== null && $v !== '';
        });
    }

    // Documents: 15-30s per spec.
    return fn_is2or_ai_integration_engine_request('POST', '/features/verify-document', [], $body);
}

/**
 * Field ID -> document_type mapping.
 * field_id 53 = "Bank Details", field_id 55 = "Commercial Registration".
 * Only these two are wired in v1; other profile fields are ignored.
 */
function fn_is2or_ai_integration_get_document_field_map()
{
    return [
        53 => 'bank_letter',
        55 => 'commercial_registration',
    ];
}

function fn_is2or_ai_integration_trigger_document_verification($company_id, $company_data = [])
{
    if (empty($company_id)) {
        return;
    }

    $company_name = !empty($company_data['company']) ? $company_data['company'] : '';
    if ($company_name === '') {
        $company_name = (string) db_get_field('SELECT company FROM ?:companies WHERE company_id = ?i', $company_id);
    }

    foreach (fn_is2or_ai_integration_get_document_field_map() as $field_id => $document_type) {
        $filename = db_get_field(
            'SELECT value FROM ?:profile_fields_data WHERE object_id = ?i AND object_type = ?s AND field_id = ?i',
            $company_id, 'S', $field_id
        );

        if (empty($filename)) {
            continue; // vendor did not upload this document
        }

        fn_is2or_ai_integration_verify_uploaded_document($company_id, $field_id, $document_type, $filename, $company_name);
    }
}

function fn_is2or_ai_integration_verify_uploaded_document($company_id, $field_id, $document_type, $filename, $company_name)
{
    // Avoid re-submitting the exact same file twice (e.g. hook fires more than once).
    $already_sent = db_get_field(
        'SELECT id FROM ?:is2or_document_verifications WHERE company_id = ?i AND field_id = ?i AND filename = ?s',
        $company_id, $field_id, $filename
    );

    if (!empty($already_sent)) {
        return;
    }

    $file_url = fn_is2or_ai_integration_get_signed_document_url($company_id, $field_id, $filename);

    if ($file_url === '') {
        return; // secret not configured, skip silently (nothing to verify against)
    }

    $expected = [
        'company_name' => $company_name,
    ];

    $result = fn_is2or_ai_integration_call_verify_document($document_type, $file_url, $company_id, $expected);

    $status        = 'failed';
    $suggestion_id = 0;
    $output        = null;
    $error         = '';

    if (!empty($result['success']) && !empty($result['data'])) {
        $data          = $result['data'];
        $suggestion_id = !empty($data['suggestion_id']) ? (int) $data['suggestion_id'] : 0;
        $status        = !empty($data['status']) ? $data['status'] : 'failed';
        $output        = !empty($data['output']) ? $data['output'] : null;
        $error         = !empty($data['error']) ? (string) $data['error'] : '';
    } else {
        $error = !empty($result['error']) ? (string) $result['error'] : 'Unknown error';
    }

    db_query(
        'INSERT INTO ?:is2or_document_verifications ?e',
        [
            'company_id'    => $company_id,
            'field_id'      => $field_id,
            'document_type' => $document_type,
            'filename'      => $filename,
            'suggestion_id' => $suggestion_id,
            'status'        => $status,
            'output'        => $output !== null ? json_encode($output) : null,
            'error'         => $error,
            'timestamp'     => TIME,
        ]
    );

    if (\Tygh\Enum\YesNo::toBool(Registry::ifGet('addons.is2or_ai_integration.debug_mode', \Tygh\Enum\YesNo::NO))) {
        fn_log_event('addons', 'is2or_ai_integration', [
            'category'   => 'document_verification',
            'company_id' => $company_id,
            'field_id'   => $field_id,
            'request'    => $file_url,
            'result'     => $result,
        ]);
    }
}

function fn_is2or_ai_integration_get_company_document_verifications($company_id)
{
    $rows = db_get_array(
        'SELECT * FROM ?:is2or_document_verifications WHERE company_id = ?i ORDER BY id DESC',
        $company_id
    );

    foreach ($rows as &$row) {
        $row['output'] = !empty($row['output']) ? json_decode($row['output'], true) : null;
    }
    unset($row);

    return $rows;
}

function fn_is2or_ai_integration_get_document_verification($id)
{
    $row = db_get_row('SELECT * FROM ?:is2or_document_verifications WHERE id = ?i', $id);

    if (!empty($row) && !empty($row['output'])) {
        $row['output'] = json_decode($row['output'], true);
    }

    return $row;
}

function fn_is2or_ai_integration_update_document_verification_status($id, $status)
{
    db_query('UPDATE ?:is2or_document_verifications SET ?u WHERE id = ?i', ['status' => $status], $id);
}

function fn_is2or_ai_integration_update_company($company_data, $company_id, $lang_code, $action)
{
    if ($action !== 'add') {
        return;
    }

    fn_is2or_ai_integration_trigger_document_verification($company_id, $company_data);
}