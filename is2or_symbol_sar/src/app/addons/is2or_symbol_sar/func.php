<?php

function fn_is2or_symbol_sar_get_css() {
    return "
@font-face {
    font-family: 'SaudiRiyalSymbol';
    src: url('../design/themes/responsive/css/addons/is2or_symbol_sar/saudiriyalsymbol.ttf') format('truetype');
}
.sar {
    font-family: SaudiRiyalSymbol, sans-serif !important;
    text-transform: lowercase !important;
}
";
}

/* HOOKS */

function fn_is2or_symbol_sar_print_order_invoices_post($order_ids, $params, $html, &$output) {
    $extra_css = fn_is2or_symbol_sar_get_css();
    $output = str_replace('</style>', $extra_css . PHP_EOL . '</style>', $output);
}

function fn_is2or_symbol_sar_send_order_invoice($order_info, $params, $subject, &$invoice, $email, $attachments) {
    // Email clients do not support @font-face, replace symbol with plain text fallback
    $invoice = str_replace('<span class="sar">ê</span>', 'SAR ', $invoice);
}
