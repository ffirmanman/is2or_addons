<?php
/**
 * Vendor Blog — Hook functions
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Hook: get_pages
 * Mencegah vendor melihat blog post vendor lain via controller pages.manage.
 * Lapisan keamanan tambahan di level query database.
 */
function fn_is2or_vendor_blog_get_pages($params, &$condition)
{
    if (
        defined('ACCOUNT_TYPE') && ACCOUNT_TYPE === 'V' &&
        !empty($params['page_type']) && $params['page_type'] === 'B'
    ) {
        $company_id = \Tygh\Registry::get('runtime.company_id');
        if ($company_id) {
            $condition .= db_quote(" AND pages.company_id = ?i", $company_id);
        }
    }
}
