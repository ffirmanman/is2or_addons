<?php

function fn_is2or_vendor_merchant_get_company_total_orders($company_id) {
    return db_get_field("SELECT COUNT(*) FROM ?:orders WHERE company_id = ?i", $company_id);
}

function fn_is2or_vendor_merchant_get_company_total_success_orders($company_id, $formatted = false) {
    $total = db_get_field("SELECT COUNT(*) FROM ?:orders WHERE company_id = ?i AND status = 'C'", $company_id);

    return $formatted ? number_format($total, 0, '.', ',') : $total;
}

function fn_is2or_vendor_merchant_get_company_success_order_rate($company_id) {
    $total_orders = fn_is2or_vendor_merchant_get_company_total_orders($company_id);
    $success_orders = fn_is2or_vendor_merchant_get_company_total_success_orders($company_id);

    if ($total_orders == 0) {
        return 0;
    }

    return round($success_orders / $total_orders * 100);
}

function fn_is2or_vendor_merchant_get_company_products_sold($company_id) {
    $result = db_get_field("SELECT SUM(amount) FROM ?:order_details od LEFT JOIN ?:orders o ON od.order_id = o.order_id WHERE o.company_id = ?i AND o.status = 'C'", $company_id);
    return number_format($result, 0, '.', ',');
}

function fn_is2or_vendor_merchant_get_company_average_products_rating($company_id) {
    return db_get_field("SELECT ROUND(AVG(rating_value), 2) FROM ?:product_reviews pr LEFT JOIN ?:products p ON pr.product_id = p.product_id WHERE p.company_id = ?i", $company_id);
}
