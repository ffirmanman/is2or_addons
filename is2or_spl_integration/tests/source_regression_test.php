<?php

declare(strict_types=1);

$addon_root = dirname(__DIR__);

$func_path = $addon_root . '/src/app/addons/is2or_spl_integration/func.php';
$template_path = $addon_root . '/src/var/themes_repository/responsive/templates/addons/is2or_spl_integration/hooks/profiles/profile_fields.override.tpl';
$api_path = $addon_root . '/src/app/addons/is2or_spl_integration/IS2OR/SplIntegration/Api.php';
$status_path = $addon_root . '/src/app/addons/is2or_spl_integration/IS2OR/SplIntegration/VerificationStatus.php';
$init_path = $addon_root . '/src/app/addons/is2or_spl_integration/init.php';
$admin_contact_template_path = $addon_root . '/src/design/backend/templates/addons/is2or_spl_integration/hooks/companies/contact_information.post.tpl';
$list_th_template_path = $addon_root . '/src/design/backend/templates/addons/is2or_spl_integration/hooks/companies/list_extra_data_th.post.tpl';
$list_td_template_path = $addon_root . '/src/design/backend/templates/addons/is2or_spl_integration/hooks/companies/list_extra_data_td.post.tpl';
$legacy_admin_template_path = $addon_root . '/src/design/backend/templates/addons/is2or_spl_integration/hooks/companies/profile_fields.post.tpl';
$legacy_list_th_template_path = $addon_root . '/src/design/backend/templates/addons/is2or_spl_integration/hooks/companies/list_extra_data_th.tpl';
$legacy_list_td_template_path = $addon_root . '/src/design/backend/templates/addons/is2or_spl_integration/hooks/companies/list_extra_data_td.tpl';

$func_source = file_get_contents($func_path);
$template_source = file_get_contents($template_path);
$api_source = file_get_contents($api_path);
$status_source = file_get_contents($status_path);
$init_source = file_get_contents($init_path);
$admin_contact_template_source = file_get_contents($admin_contact_template_path);
$list_th_template_source = file_get_contents($list_th_template_path);
$list_td_template_source = file_get_contents($list_td_template_path);
$js_path = $addon_root . '/src/js/addons/is2or_spl_integration/func.js';
$js_source = file_get_contents($js_path);

if (
    $func_source === false
    || $template_source === false
    || $api_source === false
    || $status_source === false
    || $init_source === false
    || $admin_contact_template_source === false
    || $list_th_template_source === false
    || $list_td_template_source === false
    || $js_source === false
) {
    fwrite(STDERR, "Failed to load SPL addon source files.\n");
    exit(1);
}

$errors = [];

if (strpos($func_source, 'function fn_settings_actions_addons_post_is2or_spl_integration') === false) {
    $errors[] = 'Expected the SPL addon to expose the correct CS-Cart post-status hook name.';
}

if (strpos($func_source, 'function fn_settings_actions_addons_post_addon_is2or_spl_integration') !== false) {
    $errors[] = 'Legacy misnamed post-status hook is still present in source.';
}

if (strpos($template_source, '$original.hide()') !== false) {
    $errors[] = 'Storefront profile field override still hides the original city input.';
}

if (strpos($template_source, 'PlaceAutocompleteElement') !== false) {
    $errors[] = 'Storefront profile field override still relies on PlaceAutocompleteElement.';
}

if (strpos($template_source, 'new google.maps.places.Autocomplete') === false) {
    $errors[] = 'Expected storefront city autocomplete to use the legacy Autocomplete constructor directly on the input.';
}

if (strpos($api_source, 'function verifyAddress') === false) {
    $errors[] = 'SPL Api class is missing verifyAddress method.';
}

if (strpos($api_source, 'function freeTextSearch') === false) {
    $errors[] = 'SPL Api class is missing freeTextSearch method.';
}

if (strpos($api_source, 'function generateShortAddress') === false) {
    $errors[] = 'SPL Api class lost existing generateShortAddress method.';
}

if (strpos($status_source, 'PENDING') === false || strpos($status_source, 'VERIFIED') === false) {
    $errors[] = 'VerificationStatus class is missing expected constants.';
}

if (strpos($func_source, 'function fn_is2or_spl_integration_verify_company_address') === false) {
    $errors[] = 'func.php is missing verify_company_address function.';
}

if (strpos($func_source, 'function fn_is2or_spl_integration_update_company_post') === false) {
    $errors[] = 'func.php lost existing update_company_post function.';
}

if (strpos($func_source, 'function fn_is2or_spl_integration_generate_address') === false) {
    $errors[] = 'func.php lost existing generate_address function.';
}

if (strpos($init_source, 'get_company_data_post') === false) {
    $errors[] = 'init.php is missing get_company_data_post hook registration.';
}

if (strpos($admin_contact_template_source, 'verification_status') === false) {
    $errors[] = 'Admin contact_information hook template is missing verification status UI.';
}

if (strpos($list_th_template_source, 'spl_status') === false) {
    $errors[] = 'Vendor list extra header hook template is missing SPL status label.';
}

if (strpos($list_td_template_source, 'spl_v_status') === false) {
    $errors[] = 'Vendor list extra data hook template is missing SPL status cell logic.';
}

if (file_exists($legacy_admin_template_path)) {
    $errors[] = 'Legacy admin profile_fields hook template path still exists.';
}

if (file_exists($legacy_list_th_template_path)) {
    $errors[] = 'Legacy vendor list header hook template path still exists.';
}

if (file_exists($legacy_list_td_template_path)) {
    $errors[] = 'Legacy vendor list cell hook template path still exists.';
}

if (strpos($func_source, 'function fn_is2or_spl_integration_resolve_city_id') === false) {
    $errors[] = 'SPL func.php is missing fn_is2or_spl_integration_resolve_city_id helper.';
}

if (strpos($func_source, 's_city_id') === false || strpos($func_source, 'b_city_id') === false) {
    $errors[] = 'SPL addon does not persist s_city_id / b_city_id in customer profiles.';
}

if (strpos($func_source, 'fn_cities_get_city_ids') === false) {
    $errors[] = 'SPL city resolver does not use fn_cities_get_city_ids.';
}

if (strpos($api_source, "'city_id'") === false) {
    $errors[] = 'SPL API mapping/generate flow does not expose city_id in address payloads.';
}

if (strpos($js_source, '_city_id') === false) {
    $errors[] = 'SPL JS does not populate hidden user_data[*_city_id] fields.';
}

if (strpos($js_source, 'city_id') === false) {
    $errors[] = 'SPL JS does not read city_id from generate_address response.';
}

if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }

    exit(1);
}

fwrite(STDOUT, "SPL source regression checks passed.\n");
