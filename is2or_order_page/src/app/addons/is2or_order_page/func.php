<?php

defined('BOOTSTRAP') or die('Access denied');

/**
 * Hook: inject product data into orders list
 * Runs after fn_get_orders returns data
 */
function fn_is2or_order_page_get_orders_post(&$params, &$orders)
{
    if (empty($orders)) {
        return;
    }

    $order_ids = array_column($orders, 'order_id');

    if (empty($order_ids)) {
        return;
    }

    $products = db_get_array(
        "SELECT op.order_id, op.product_id, pd.product as product, op.amount, op.price,
                op.extra,
                co.company as company_name
         FROM ?:order_details op
         LEFT JOIN ?:orders o ON o.order_id = op.order_id
         LEFT JOIN ?:companies co ON co.company_id = o.company_id
         LEFT JOIN ?:product_descriptions pd ON pd.product_id = op.product_id
         WHERE op.order_id IN (?n)
         AND pd.lang_code = ?s",
        $order_ids,
        CART_LANGUAGE
    );

    // Group by order_id
    $order_products = [];
    foreach ($products as $product) {
        if (!empty($product['extra'])) {
            $extra = @unserialize($product['extra']);
            $product['extra_parsed'] = is_array($extra) ? $extra : [];
        }
        $order_products[$product['order_id']][] = $product;
    }

    // Fetch images
    $product_ids = array_unique(array_column($products, 'product_id'));
    if (!empty($product_ids)) {
        $pairs = [];
        foreach ($product_ids as $product_id) {
            $image = fn_get_image_pairs($product_id, 'product', 'M', true, true, CART_LANGUAGE);
            if (!empty($image)) {
                $pairs[$product_id] = $image;
            }
        }
        foreach ($orders as &$order) {
            $oid = $order['order_id'];
            $order['products'] = isset($order_products[$oid]) ? $order_products[$oid] : [];
            foreach ($order['products'] as &$p) {
                if (!empty($pairs[$p['product_id']])) {
                    $p['main_pair'] = $pairs[$p['product_id']];
                }
            }
            unset($p);
        }
        unset($order);
    } else {
        foreach ($orders as &$order) {
            $order['products'] = isset($order_products[$order['order_id']]) 
                ? $order_products[$order['order_id']] 
                : [];
        }
        unset($order);
    }
}