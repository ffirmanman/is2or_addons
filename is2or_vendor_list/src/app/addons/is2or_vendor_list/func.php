<?php

defined('BOOTSTRAP') or die('Access denied');

const IS2OR_VENDOR_LIST_CATEGORY_COLUMN = 'is2or_vendor_category_ids';

function fn_is2or_vendor_list_install()
{
    if (!fn_is2or_vendor_list_company_category_column_exists()) {
        db_query(
            'ALTER TABLE ?:companies ADD COLUMN `' . IS2OR_VENDOR_LIST_CATEGORY_COLUMN . "` VARCHAR(255) NOT NULL DEFAULT ''"
        );
        fn_is2or_vendor_list_company_category_column_exists(true);
    }
}

function fn_is2or_vendor_list_company_category_column_exists($reset_cache = false)
{
    static $exists = null;

    if ($exists === null || $reset_cache) {
        $exists = (bool) db_get_field('SHOW COLUMNS FROM ?:companies WHERE Field = ?s', IS2OR_VENDOR_LIST_CATEGORY_COLUMN);
    }

    return $exists;
}

function fn_is2or_vendor_list_pending_company_category_ids($category_ids = null, $clear = false)
{
    static $pending_category_ids = null;

    if ($clear) {
        $pending_category_ids = null;
        return null;
    }

    if (func_num_args() > 0) {
        $pending_category_ids = $category_ids;
    }

    return $pending_category_ids;
}

function fn_is2or_vendor_list_update_company_pre(&$company_data, $company_id, $lang_code, &$can_update)
{
    if (!array_key_exists(IS2OR_VENDOR_LIST_CATEGORY_COLUMN, $company_data)) {
        fn_is2or_vendor_list_pending_company_category_ids(null, true);
        return;
    }

    if (!fn_is2or_vendor_list_company_category_column_exists()) {
        unset($company_data[IS2OR_VENDOR_LIST_CATEGORY_COLUMN]);
        fn_is2or_vendor_list_pending_company_category_ids(null, true);
        return;
    }

    fn_is2or_vendor_list_pending_company_category_ids(
        fn_is2or_vendor_list_normalize_category_ids($company_data[IS2OR_VENDOR_LIST_CATEGORY_COLUMN])
    );

    unset($company_data[IS2OR_VENDOR_LIST_CATEGORY_COLUMN]);
}

function fn_is2or_vendor_list_update_company($company_data, $company_id, $lang_code, $action)
{
    $category_ids = fn_is2or_vendor_list_pending_company_category_ids();

    fn_is2or_vendor_list_pending_company_category_ids(null, true);

    if ($category_ids === null || empty($company_id) || !fn_is2or_vendor_list_company_category_column_exists()) {
        return;
    }

    db_query(
        'UPDATE ?:companies SET ?u WHERE company_id = ?i',
        [IS2OR_VENDOR_LIST_CATEGORY_COLUMN => $category_ids],
        $company_id
    );
}

function fn_is2or_vendor_list_get_block_vendors($value = '', array $block = [], array $block_scheme = [])
{
    $params = !empty($block['content']) && is_array($block['content']) ? $block['content'] : [];

    return fn_is2or_get_vendor_list($params);
}

function fn_is2or_get_vendor_list($params = [])
{
    if (!is_array($params)) {
        $params = [];
    }

    $conditions = [
        db_quote('c.status = ?s', 'A'),
    ];

    $has_category_column = fn_is2or_vendor_list_company_category_column_exists();
    $category_ids = fn_is2or_vendor_list_parse_category_ids($params['category_ids'] ?? $params['category_id'] ?? []);
    if ($has_category_column && !empty($category_ids)) {
        $category_conditions = [];
        foreach ($category_ids as $category_id) {
            $category_conditions[] = db_quote(
                'FIND_IN_SET(?i, REPLACE(c.' . IS2OR_VENDOR_LIST_CATEGORY_COLUMN . ', ?s, ?s))',
                $category_id,
                ' ',
                ''
            );
        }
        $conditions[] = '(' . implode(' OR ', $category_conditions) . ')';
    }

    $sort_map = [
        'company' => 'c.company',
    ];
    $sort = !empty($params['sort']) ? (string) $params['sort'] : 'company';
    $sort_field = $sort_map[$sort] ?? $sort_map['company'];
    $sort_order = !empty($params['sort_order']) && strtolower((string) $params['sort_order']) === 'desc'
        ? 'DESC'
        : 'ASC';

    $limit = '';
    if (!empty($params['limit']) && (int) $params['limit'] > 0) {
        $limit = db_quote(' LIMIT ?i', (int) $params['limit']);
    }

    $category_column_select = $has_category_column
        ? 'c.' . IS2OR_VENDOR_LIST_CATEGORY_COLUMN
        : "''";

    $vendors = db_get_array(
        "SELECT
            c.company_id,
            c.company,
            c.email,
            c.phone,
            c.address,
            c.city,
            " . $category_column_select . " AS " . IS2OR_VENDOR_LIST_CATEGORY_COLUMN . ",
            vp_descr.plan AS vendor_plan_name,
            i_logo.image_path AS logo_path,
            i_banner.image_path AS banner_path,
            il_banner.detailed_id AS banner_image_id,
            c.status
        FROM
            ?:companies AS c
        LEFT JOIN ?:vendor_plans AS vp
            ON c.plan_id = vp.plan_id
        LEFT JOIN ?:vendor_plan_descriptions AS vp_descr
            ON vp.plan_id = vp_descr.plan_id AND vp_descr.lang_code = 'en'
        LEFT JOIN ?:images_links AS il_logo
            ON il_logo.pair_id = (
                SELECT MAX(inner_il.pair_id)
                FROM ?:images_links AS inner_il
                WHERE inner_il.object_id = c.company_id
                AND inner_il.object_type = 'logos'
            )
        LEFT JOIN ?:images AS i_logo
            ON i_logo.image_id = il_logo.image_id
        LEFT JOIN ?:images_links AS il_banner
            ON il_banner.pair_id = (
                SELECT MAX(inner_il.pair_id)
                FROM ?:images_links AS inner_il
                WHERE inner_il.object_id = c.company_id
                AND inner_il.object_type = 'abt__v_info_block_bg'
            )
        LEFT JOIN ?:images AS i_banner
            ON i_banner.image_id = il_banner.detailed_id
        WHERE
            " . implode(' AND ', $conditions) . "
        ORDER BY
            {$sort_field} {$sort_order}
        {$limit}"
    );

    if (empty($vendors)) {
        return [];
    }

    foreach ($vendors as &$vendor) {
        $cid = $vendor['company_id'];

        // Logo
        $logos = fn_get_logos($cid);
        $vendor['logo_path'] = !empty($logos['theme']['image']['image_path'])
            ? $logos['theme']['image']['image_path']
            : '';

        // Banner
        $vendor['banner_path'] = '';
        if (!empty($vendor['banner_image_id'])) {
            $banner = fn_get_image($vendor['banner_image_id'], 'detailed');
            if (!empty($banner['image_path'])) {
                $vendor['banner_path'] = $banner['image_path'];
            }
        }
    }
    unset($vendor);

    return $vendors;
}

function fn_is2or_vendor_list_normalize_category_ids($category_ids)
{
    return implode(',', fn_is2or_vendor_list_parse_category_ids($category_ids));
}

function fn_is2or_vendor_list_parse_category_ids($category_ids)
{
    if (empty($category_ids)) {
        return [];
    }

    if (!is_array($category_ids)) {
        $category_ids = preg_split('/[\s,]+/', (string) $category_ids, -1, PREG_SPLIT_NO_EMPTY);
    }

    $normalized_ids = [];
    foreach ($category_ids as $category_id) {
        if (is_array($category_id)) {
            $normalized_ids = array_merge($normalized_ids, fn_is2or_vendor_list_parse_category_ids($category_id));
            continue;
        }

        $category_id = (int) $category_id;
        if ($category_id > 0) {
            $normalized_ids[$category_id] = $category_id;
        }
    }

    return array_values($normalized_ids);
}
