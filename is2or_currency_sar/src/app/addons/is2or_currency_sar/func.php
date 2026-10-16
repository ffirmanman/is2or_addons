<?php

function fn_is2or_currency_sar_get_company_total_orders($company_id) {
    return db_get_field("SELECT COUNT(*) FROM ?:orders WHERE company_id = ?i", $company_id);
}

function fn_is2or_currency_sar_get_company_total_success_orders($company_id, $formatted = false) {
    $total = db_get_field("SELECT COUNT(*) FROM ?:orders WHERE company_id = ?i AND status = 'C'", $company_id);

    return $formatted ? number_format($total, 0, '.', ',') : $total;
}

function fn_is2or_currency_sar_get_company_success_order_rate($company_id) {
    $total_orders = fn_is2or_currency_sar_get_company_total_orders($company_id);
    $success_orders = fn_is2or_currency_sar_get_company_total_success_orders($company_id);

    if ($total_orders == 0) {
        return 0;
    }

    return round($success_orders / $total_orders * 100);
}

function fn_is2or_currency_sar_get_company_products_sold($company_id) {
    $result = db_get_field("SELECT SUM(amount) FROM ?:order_details od LEFT JOIN ?:orders o ON od.order_id = o.order_id WHERE o.company_id = ?i AND o.status = 'C'", $company_id);
    return number_format($result, 0, '.', ',');
}

function fn_is2or_currency_sar_get_company_average_products_rating($company_id) {
    return db_get_field("SELECT ROUND(AVG(rating_value), 2) FROM ?:product_reviews pr LEFT JOIN ?:products p ON pr.product_id = p.product_id WHERE p.company_id = ?i", $company_id);
}

function fn_is2or_currency_sar_get_sar_symbol_css() {
    return "
@font-face {
    font-family: 'SaudiRiyalSymbol';
    src: url('../design/themes/responsive/css/addons/is2or_currency_sar/saudiriyalsymbol.ttf') format('truetype');
}
.sar {
    font-family: SaudiRiyalSymbol, sans-serif !important;
    text-transform: lowercase !important;
}
";
}

/* HOOKS FUNCTIONS */
function fn_is2or_currency_sar_print_order_invoices_post($order_ids, $params, $html, &$output) {
    $extra_css = fn_is2or_currency_sar_get_sar_symbol_css();
    $output = str_replace('</style>', $extra_css . PHP_EOL . '</style>', $output);
}

function fn_is2or_currency_sar_send_order_invoice($order_info, $params, $subject, &$invoice, $email, $attachments) {
    // Email not support @font-face
    $invoice = str_replace('<span class="sar">ê</span>', 'SAR ', $invoice);
}
/* HOOKS FUNCTIONS */