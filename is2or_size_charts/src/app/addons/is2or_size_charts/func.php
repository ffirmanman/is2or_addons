<?php

use Tygh\Registry;

function fn_is2or_size_charts_get_customer_size_names() {
    return [
        'shoulder' => __('is2or_size_charts.shoulder_width'),
        'chest' => __('is2or_size_charts.chest_bust_size'),
        // 'bust' => __('is2or_size_charts.bust_size'),
        'waist' => __('is2or_size_charts.waist_size'),
        'hips' => __('is2or_size_charts.hips_size'),
        'thigh' => __('is2or_size_charts.thigh_size'),
        'inseam' => __('is2or_size_charts.inseam_length'),
        'outseam' => __('is2or_size_charts.outseam_length'),
        'foot_w' => __('is2or_size_charts.foot_width'),
        'foot_l' => __('is2or_size_charts.foot_length'),
    ];
}

function fn_is2or_size_charts_get_templates($params = [], $items_per_page = 10) {
    $table_name = '?:size_chart_templates';

    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page,
    ];
    $params = array_merge($default_params, $params);

    $fields = [
        "$table_name.*",
    ];
    
    $sortings = [
        'template_id' => "$table_name.template_id",
        'title' => "$table_name.title",
    ];

    $condition = $limit = '';

    $sorting = db_sort($params, $sortings, 'titlte', 'asc');
    
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM $table_name WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $data = db_get_hash_array(
        "SELECT ?p FROM $table_name WHERE 1 ?p ?p ?p",
        'template_id',
        implode(', ', $fields),
        $condition,
        $sorting,
        $limit
    );

    foreach ($data as &$row) {
        $row['columns'] = json_decode($row['columns'], true);
        if (!is_array($row['columns'])) {
            $row['columns'] = [];
        }

        $row['columns_titles'] = implode(', ', array_column($row['columns'], 'title'));
    }

    return [$data, $params];
}

function fn_is2or_size_charts_get_templates_list() {
    list($templates) = fn_is2or_size_charts_get_templates();

    $out = [];
    foreach ($templates as $template_id => $template) {
        $out[$template_id] = $template['title'];
    }

    return $out;
}

function fn_is2or_size_charts_get_template($template_id) {
    $template = db_get_row("SELECT * FROM ?:size_chart_templates WHERE template_id=?i", $template_id);

    $template['columns'] = json_decode($template['columns'], true);
    if (!is_array($template['columns'])) {
        $template['columns'] = [];
    }
    
    return $template;
}

function fn_is2or_size_charts_update_template($template_id, $data) {
    if (!empty($data['columns']) && is_array($data['columns'])) {
        $data['columns'] = array_filter($data['columns'], function ($column) {
            return !empty($column['code']) && !empty($column['title']);
        });

        $data['columns'] = json_encode($data['columns']);
    }

    if ($template_id) {
        db_query("UPDATE ?:size_chart_templates SET ?u WHERE template_id=?i", $data, $template_id);
    } else {
        $template_id = db_query("INSERT INTO ?:size_chart_templates ?e", $data);
    }

    return $template_id;
}

function fn_is2or_size_charts_delete_template($template_id) {
    return db_query("DELETE FROM ?:size_chart_templates WHERE template_id=?i", $template_id);
}

function fn_is2or_size_charts_get_charts($params = [], $items_per_page = 10) {
    $table_name = '?:size_charts';

    $default_params = [
        'company_id' => Registry::get('runtime.company_id'),
        'page' => 1,
        'items_per_page' => $items_per_page,
    ];
    $params = array_merge($default_params, $params);

    $fields = [
        "$table_name.*",
        "size_chart_tempaltes.title as template_name",
    ];
    
    $sortings = [
        'title' => "$table_name.title",
        'template_id' => "$table_name.template_id",
        'status' => "$table_name.status",
    ];

    $condition = $limit = '';

    $sorting = db_sort($params, $sortings, 'title', 'asc');

    $join = db_quote(" LEFT JOIN ?:size_chart_templates size_chart_tempaltes ON size_chart_tempaltes.template_id = $table_name.template_id");

    if (!empty($params['company_id'])) {
        $condition .= db_quote(" AND $table_name.company_id = ?i", $params['company_id']);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(" AND $table_name.status = ?s", $params['status']);
    }

    if (!empty($params['template_id'])) {
        $condition .= db_quote(" AND $table_name.template_id = ?i", $params['template_id']);
    }
    
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM $table_name WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $data = db_get_hash_array(
        "SELECT ?p FROM $table_name $join WHERE 1 ?p ?p ?p",
        'chart_id',
        implode(', ', $fields),
        $condition,
        $sorting,
        $limit
    );

    foreach ($data as &$row) {
        $row['variants'] = json_decode($row['variants'], true);
        if (!is_array($row['variants'])) {
            $row['variants'] = [];
        }

        $row['applies'] = json_decode($row['applies'], true);
        if (!is_array($row['applies'])) {
            $row['applies'] = [];
        }

        if (!empty($row['applies'])) {
            foreach ($row['applies'] as $key => $value) {
                $row['applies'][$key] = array_filter(explode(',', $value));
            }
        }
    }

    return [$data, $params];
}

function fn_is2or_size_charts_get_chart($chart_id, $lang_code = CART_LANGUAGE) {
    $data = db_get_row("SELECT sc.*, cd.text_before, cd.text_after  FROM ?:size_charts sc LEFT JOIN ?:size_chart_descriptions cd ON sc.chart_id=cd.chart_id WHERE sc.chart_id=?i AND cd.lang_code=?s", $chart_id, $lang_code);

    $data['variants'] = json_decode($data['variants'], true);
    if (!is_array($data['variants'])) {
        $data['variants'] = [];
    }

    $data['applies'] = json_decode($data['applies'], true);
    if (!is_array($data['applies'])) {
        $data['applies'] = [];
    }

    $data['customer_sizes'] = json_decode($data['customer_sizes'], true);
    if (!is_array($data['customer_sizes'])) {
        $data['customer_sizes'] = [];
    }

    $data['template_name'] = '';
    $data['template'] = [];

    $template = fn_is2or_size_charts_get_template($data['template_id']);
    if ($template) {
        $data['template_name'] = $template['title'];
        $data['template'] = $template;

        $data['chart'] = [
            'title' => $data['title'],
            'headers' => [],
            'rows' => [],
            'text_before' => $data['text_before'],
            'text_after' => $data['text_after'],
        ];

        foreach ($template['columns'] as $column) {
            $data['chart']['headers'][] = $column['title'];
        }

        foreach ($data['variants'] as $variant) {
            $cRow = [];
            $cRow['__name'] = $variant['__name'] ?? '';
            foreach ($template['columns'] as $column) {
                if (isset($variant[$column['code']])) {
                    $cRow[$column['code']] = $variant[$column['code']];
                }
            }

            if ($cRow) {
                $data['chart']['rows'][] = $cRow;
            }
        }
    }

    return $data;
}

function fn_is2or_size_charts_update_chart($chart_id, $data) {
    $company_id = Registry::get('runtime.company_id');

    // Init description data
    $description = [
        'text_before' => $data['text_before'],
        'text_after' => $data['text_after'],
    ];
    unset($data['text_before'], $data['text_after']);

    $variants = isset($data['variants'])
        ? $data['variants']
        : null;

    $data['variants'] = json_encode(fn_is2or_size_charts_sanitize_chart_variants($variants));

    $data['company_id'] = $company_id;

    if ($company_id) {
        $data['applies']['vendors'] = $company_id;
    }

    // if (empty($company_id)) {
    //     if (isset($data['applies']) && isset($data['applies']['vendors'])) {
    //         if (empty($data['applies']['vendors'])) {
    //             $data['company_id'] = 0;
    //         } else {
    //             $vendor_ids = explode(',', $data['applies']['vendors']);

    //             if (count($vendor_ids) == 1) {
    //                 $data['company_id'] = reset($vendor_ids);
    //             } elseif (count($vendor_ids) > 1) {
    //                 $data['company_id'] = 0;
    //             }
    //         }
    //     }

    //     $data['applies']['vendors'] = '';
    // }
    $data['applies'] = json_encode($data['applies']);
    $data['customer_sizes'] = json_encode($data['customer_sizes']);

    if ($chart_id) {
        db_query("UPDATE ?:size_charts SET ?u WHERE chart_id=?i", $data, $chart_id);
    } else {
        $chart_id = db_query("INSERT INTO ?:size_charts ?e", $data);
    }

    // Update description
    db_query(
        "REPLACE INTO ?:size_chart_descriptions ?e",
        array_merge(
            $description,
            [
                'chart_id' => $chart_id,
                'lang_code' => CART_LANGUAGE
            ]
        )
    );

    return $chart_id;
}

function fn_is2or_size_charts_sanitize_chart_variants($variants) {
    if (!is_array($variants)) {
        $variants = [];
    }

    foreach ($variants as $idx => &$variant) {
        $variant['position'] = trim($variant['position']);
        if ($variant['position'] == '') {
            $variant['position'] = 0;
        }

        $keys = array_keys($variant);
        $votes = 0;

        foreach ($keys as $key) {
            if (empty($variant[$key]) && $key != 'position') {
                $votes++;
            }
        }

        if ($votes == count($keys) - 1) {
            unset($variants[$idx]);
        }
    }

    usort($variants, function ($a, $b) {
        if ($a['position'] == $b['position']) return 0;
        return $a['position'] > $b['position'] ? 1 : -1;
    });

    return array_values($variants);
}

function fn_is2or_size_charts_delete_chart($chart_id) {
    db_query("DELETE FROM ?:size_charts WHERE chart_id=?i", $chart_id);
    db_query("DELETE FROM ?:size_chart_descriptions WHERE chart_id=?i", $chart_id);
}

function fn_is2or_size_charts_get_product_charts($product_id, $user_id = 0) {
    $product = [];
    $product['product_id'] = $product_id;
    $product['variant_ids'] = fn_is2or_size_charts_get_product_variant_ids($product_id);
    $product['category_ids'] = fn_is2or_size_charts_get_product_category_ids($product_id);
    $product['company_id'] = fn_is2or_size_charts_get_product_company_id($product_id);

    list($charts) = fn_is2or_size_charts_get_charts([
        'status' => 'A',
    ]);

    $matched = [];
    foreach ($charts as $chart) {
        if (fn_is2or_size_charts_does_chart_applies($product, $chart)) {
            $matched[] = $chart;
        }
    }

    $out = [];
    if ($matched) {
        foreach ($matched as $match) {
            $out[] = fn_is2or_size_charts_get_chart($match['chart_id']);
        }
    }

    if ($user_id) {
        $user_info = fn_get_user_info($user_id);
        fn_is2or_size_charts_gather_product_size_recommendation($out, $user_info['is2or_sizes']);
    }

    return $out;
}

function fn_is2or_size_charts_get_product_variant_ids($product_id) {
    return db_get_fields(
        "SELECT variant_id 
            FROM ?:product_features_values 
            WHERE PRODUCT_id=?i AND lang_code=?s AND feature_id IN (
                SELECT f.feature_id
                FROM ?:product_features f 
                WHERE purpose='organize_catalog' AND feature_style='brand'
            )",
        $product_id,
        CART_LANGUAGE
    );
}

function fn_is2or_size_charts_get_product_category_ids($product_id) {
    return db_get_fields("SELECT category_id FROM ?:products_categories WHERE product_id=?i", $product_id);
}

function fn_is2or_size_charts_get_product_company_id($product_id) {
    return db_get_field("SELECT company_id FROM ?:products WHERE product_id=?i", $product_id);
}

function fn_is2or_size_charts_does_chart_applies($product, $chart) {
    $reqVotes = 0;
    $applies = [
        'categories' => 'category_ids',
        'products' => 'product_id',
        'vendors' => 'company_id',
        'brands' => 'variant_ids'
    ];

    foreach (array_keys($applies) as $key) {
        if (!empty($chart['applies'][$key])) {
            $reqVotes++;
        }
    }

    if ($reqVotes == 0) {
        return false;
    }

    $votes = 0;
    foreach ($applies as $key => $field) {
        if (isset($product[$field]) && isset($chart['applies'][$key])) {
            if (is_array($product[$field])) {
                $tmp = array_intersect($product[$field], $chart['applies'][$key]);
                if (count($tmp)) {
                    $votes++;
                }
            } else {
                if (in_array($product[$field], $chart['applies'][$key])) {
                    $votes++;
                }
            }
        }
    }

    return ($votes == $reqVotes);
}

function fn_is2or_size_charts_get_brands($params = [], $items_per_page = 10) {
    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page,
    ];
    $params = array_merge($default_params, $params);

    $fields = [
        'fv.feature_id',
        'fv.variant_id as brand_id',
        'variant as brand',
        'fd.description as feature',
    ];

    $condition = $limit = $join = '';

    $sortings = [
        'brand' => 'fvd.variant',
    ];
    $sorting = db_sort($params, $sortings, 'brand', 'asc');

    $join .= db_quote(" LEFT JOIN ?:product_features_descriptions fd ON fd.feature_id = fv.feature_id AND fd.lang_code = ?s", CART_LANGUAGE);
    $join .= db_quote(" LEFT JOIN ?:product_feature_variant_descriptions fvd ON fv.variant_id = fvd.variant_id AND fvd.lang_code = ?s", CART_LANGUAGE);

    if (!empty($params['feature_id'])) {
        $feature_ids = [$params['feature_id']];
    } else {
        $features = fn_is2or_size_charts_get_features();
        $feature_ids = array_keys($features);
    }

    $condition .= db_quote(" AND fv.feature_id IN (?n)", $feature_ids);

    if (!empty($params['q'])) {
        $condition .= db_quote(" AND fvd.variant LIKE ?s", "%{$params['q']}%");
    }

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT count(*) FROM ?:product_feature_variants fv $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $data = db_get_array(
        "SELECT ?p FROM ?:product_feature_variants fv $join WHERE 1 ?p ?p ?p",
        implode(', ', $fields),
        $condition,
        $sorting,
        $limit
    );

    return [$data, $params];
}

function fn_is2or_size_charts_get_brand($brand_id) {
    $sql = "SELECT fv.feature_id, fv.variant_id AS brand_id, variant AS brand ,fd.description as feature
        FROM cscart_product_feature_variants fv
        LEFT JOIN cscart_product_feature_variant_descriptions fvd ON fv.variant_id =fvd.variant_id AND fvd.lang_code='en'
        LEFT JOIN cscart_product_features_descriptions fd ON fv.feature_id=fd.feature_id AND fd.lang_code='en'
        WHERE fv.variant_id = ?i";

    return db_get_row($sql, $brand_id);
}

function fn_is2or_size_charts_get_features() {
    return db_get_hash_single_array(
        "SELECT f.feature_id, fd.description title
        FROM ?:product_features f 
        LEFT JOIN ?:product_features_descriptions fd ON f.feature_id=fd.feature_id AND fd.lang_code=?s
        WHERE purpose='organize_catalog' AND feature_style='brand'",
        ['feature_id', 'title'],
        CART_LANGUAGE
    );
}

function fn_is2or_size_charts_update_user_sizes($user_id, $sizes) {
    if (is_array($sizes)) {
        $sizes = json_encode($sizes);
    }

    db_query("UPDATE ?:users SET is2or_sizes=?s WHERE user_id=?i", $sizes, $user_id);
}

function fn_is2or_size_charts_gather_product_size_recommendation(&$charts, array $sizes)
{
    foreach ($charts as &$chart) {
        if (empty($chart['customer_sizes']) || empty($chart['chart']['rows']) || empty(array_filter($sizes))) {
            continue;
        }

        $customer_sizes = array_filter($chart['customer_sizes']);

        $best_row = null;
        $best_gap = null;

        foreach ($chart['chart']['rows'] as $row) {
            $gap = 0;
            $valid = true;

            foreach ($customer_sizes as $column_key => $size_key) {
                if (!isset($row[$column_key], $sizes[$size_key])) {
                    continue;
                }

                $diff = (float) $row[$column_key] - (float) $sizes[$size_key];

                if ($diff < 0) {
                    $valid = false;
                    break;
                }

                $gap += $diff;
            }

            if (!$valid) {
                continue;
            }

            if ($best_gap === null || $gap < $best_gap) {
                $best_gap = $gap;
                $best_row = $row;
            }
        }

        if ($best_row !== null) {
            $chart['chart']['recommendation'] = $best_row;
            $chart['chart']['recommendation_gap'] = $best_gap;
        }
    }
}

/* HOOKS FUNCTIONS */
function fn_is2or_size_charts_get_user_info($user_id, $get_profile, $profile_id, &$user_data) {
    if (isset($user_data['is2or_sizes'])) {
        $user_data['is2or_sizes'] = json_decode($user_data['is2or_sizes'], true);

        if (!is_array($user_data['is2or_sizes'])) {
            $user_data['is2or_sizes'] = [];
        }
    }
}

function fn_is2or_size_charts_update_user_pre($user_id, &$user_data, $auth, $ship_to_another, $notify_user, $can_update) {
    if (isset($user_data['is2or_sizes']) && is_array($user_data['is2or_sizes'])) {
        $user_data['is2or_sizes'] = json_encode($user_data['is2or_sizes']);
    }
}
/* HOOKS FUNCTIONS */