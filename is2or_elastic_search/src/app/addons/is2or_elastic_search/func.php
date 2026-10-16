<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Http;
use Tygh\Registry;

/**
 * Hook: Automatically triggered after admin clicks Save Product
 */
function fn_is2or_elastic_search_update_product_post($product_data, $product_id, $lang_code, $action) 
{
    $product = fn_get_product_data($product_id, $_SESSION['auth'], $lang_code);
    if (!empty($product)) {
        fn_is2or_es_sync_product($product);
    }
}

/**
 * Function: Send product data to Elasticsearch (Indexing)
 */
function fn_is2or_es_sync_product($product) 
{
    $es_settings = Registry::get('addons.is2or_elastic_search');
    
    $url = sprintf(
        '%s://%s:%s/%s/_doc/%s',
        $es_settings['es_scheme'],
        $es_settings['es_host'],
        $es_settings['es_port'],
        $es_settings['es_index_name'],
        $product['product_id']
    );

    $company_data = db_get_row(
        "SELECT company, address, city, state, country
        FROM ?:companies 
        WHERE company_id = ?i",
        $product['company_id']
    ) ?: [];

    $document = [
        'product_id'   => $product['product_id'],
        'product'      => $product['product'],
        'product_code' => $product['product_code'],
        'price'        => (float) $product['price'],
        'description'  => strip_tags($product['full_description']),
        'status'       => $product['status'],
        'company'      => [
            'name'                 => $company_data['company'] ?? null,
            'address'              => $company_data['address'] ?? null,
            'city'                 => $company_data['city'] ?? null,
            'state'                => $company_data['state'] ?? null,
            'country'              => $company_data['country'] ?? null,
        ],
        'updated_at'   => date('Y-m-d H:i:s')
    ];

    $headers = ['Content-Type: application/json'];
    if (!empty($es_settings['es_auth_header'])) {
        $headers[] = 'Authorization: ' . $es_settings['es_auth_header'];
    }

    Http::put($url, json_encode($document), ['headers' => $headers]);
}

/**
 * Hook: Get products
 * Used to redirect search queries to Elasticsearch
 */
function fn_is2or_elastic_search_get_products(
    $params,
    $fields,
    $sortings,
    $condition,
    &$join,
    $sorting,
    $group_by,
    $lang_code,
    $having
) {
    // Execute only on frontend (Area 'C') and when search keyword 'q' exists
    if (AREA == 'C' && !empty($params['q'])) {
        $search_results = fn_is2or_elastic_search_query($params['q']);
        
        if (!empty($search_results['hits']['hits'])) {
            $ids = array_column($search_results['hits']['hits'], '_id');
            // Add SQL condition so MySQL only fetches product IDs returned by Elasticsearch
            $condition .= db_quote(" AND products.product_id IN (?n)", $ids);
        } else {
            // If Elasticsearch returns no results, force MySQL to return empty result
            $condition .= " AND 1=0";
        }
    }
}

/**
 * Function: Execute search query to Elasticsearch
 */
function fn_is2or_elastic_search_query($term) 
{
    $es_settings = Registry::get('addons.is2or_elastic_search');
    
    $url = sprintf(
        '%s://%s:%s/%s/_search',
        $es_settings['es_scheme'],
        $es_settings['es_host'],
        $es_settings['es_port'],
        $es_settings['es_index_name']
    );

    $query = [
        "query" => [
            "multi_match" => [
                "query"     => $term,
                "fields"    => ["product^3", "product_code^2", "description"],
                "fuzziness" => "AUTO"
            ]
        ]
    ];

    $headers = ['Content-Type: application/json'];
    if (!empty($es_settings['es_auth_header'])) {
        $headers[] = 'Authorization: ' . $es_settings['es_auth_header'];
    }

    $response = Http::get($url, [], [
        'headers' => $headers,
        'data'    => json_encode($query)
    ]);

    return json_decode($response, true);
}
