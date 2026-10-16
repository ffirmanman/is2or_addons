<?php

use Tygh\Languages\Languages;

function fn_is2or_invoice_stamp_install() {
    $snippets = ['stamp', 'signature'];

    foreach ($snippets as $snippet) {
        $snippet_code = "vendor_$snippet";

        if (!db_get_field("SELECT snippet_id FROM ?:template_snippets WHERE code=?s", $snippet_code)) {
            $template = '{% if is.'.$snippet.' %}<img src="{{ is.'.$snippet.' }}" style="width: 150px;">{% endif %}';

            $data = [
                'code' => $snippet_code,
                'type' => 'order_invoice',
                'default_template' => $template,
                'status' => 'A'
            ];
            $snippet_id = db_query("INSERT INTO ?:template_snippets ?e", $data);

            foreach (array_keys(Languages::getAll()) as $lang) {
                $description_data = [
                    'snippet_id' => $snippet_id,
                    'lang_code' => $lang,
                    'name' => "Vendor $snippet"
                ];
                db_query("INSERT INTO ?:template_snippet_descriptions ?e", $description_data);
            }
        }
    }
}

function fn_is2or_invoice_stamp_uninstall() {
    $snippets = ['stamp', 'signature'];

    foreach ($snippets as $snippet) {
        $snippet_code = "vendor_$snippet";
        if ($snippet_id = db_get_field("SELECT snippet_id FROM ?:template_snippets WHERE code=?s", $snippet_code)) {
            db_query("DELETE FROM ?:template_snippets WHERE snippet_id=?i", $snippet_id);
            db_query("DELETE FROM ?:template_snippet_descriptions WHERE snippet_id=?i", $snippet_id);
        }
    }
}

function fn_invoice_stamp_get_image_url($company_id, $image_type) {
    $pair_data = fn_get_image_pairs($company_id, $image_type, 'M', true, false);
    
    $out = false;

    if (!empty($pair_data)) {
        $out = $pair_data['icon']['image_path'];
    }
    
    return $out;
}

/* HOOKS FUNCTIONS */
function fn_is2or_invoice_stamp_update_company($company_data, $company_id, $lang_code, $action) {
    fn_attach_image_pairs('vendor_stamp', 'vendor_stamp', $company_id);
    fn_attach_image_pairs('vendor_sign', 'vendor_sign', $company_id);
}

function fn_is2or_invoice_stamp_get_company_data_post($company_id, $lang_code, $extra, &$company_data) {
    $company_data['vendor_stamp'] = fn_get_image_pairs($company_id, 'vendor_stamp', 'M', true, false);
    $company_data['vendor_sign'] = fn_get_image_pairs($company_id, 'vendor_sign', 'M', true, false);
}
