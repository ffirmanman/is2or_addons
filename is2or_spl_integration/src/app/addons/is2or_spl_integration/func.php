<?php

use IS2OR\SplIntegration\Api;
use IS2OR\SplIntegration\VerificationStatus;
use Tygh\Enum\ProfileFieldSections;
use Tygh\Enum\ProfileTypes;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Get profile fields for settings dropdowns
 */
function fn_is2or_spl_integration_get_profile_fields($type = ProfileTypes::CODE_USER)
{
    $params['profile_type'] = $type;
    if ($type == ProfileTypes::CODE_USER) {
        $params['user_type'] = 'C';
    }
    $profile_fields = fn_get_profile_fields('C', [], CART_LANGUAGE, $params);
    $profile_fields_sections = fn_get_profile_fields_sections();

    $out = [];
    foreach ($profile_fields as $section => $fields) {
        if ($section == ProfileFieldSections::ESSENTIALS) continue;
        foreach ($fields as $field_id => $field) {
            $out[$field_id] = $field['description'] . ' (' . $profile_fields_sections[$section]['section_name'] . ')';
        }
    }

    return $out;
}

function fn_settings_variants_addons_is2or_spl_integration_s_address() { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_s_country() { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_s_state()   { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_s_city()    { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_s_zipcode() { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_b_address() { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_b_country() { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_b_state()   { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_b_city()    { return fn_is2or_spl_integration_get_profile_fields(); }
function fn_settings_variants_addons_is2or_spl_integration_b_zipcode() { return fn_is2or_spl_integration_get_profile_fields(); }

/**
 * Ensure durable city_id columns exist on user profiles.
 */
function fn_is2or_spl_integration_ensure_user_profile_city_id_columns()
{
    $profile_columns = [
        's_city_id' => "int(11) unsigned NOT NULL DEFAULT 0",
        'b_city_id' => "int(11) unsigned NOT NULL DEFAULT 0",
    ];

    foreach ($profile_columns as $column_name => $column_definition) {
        $exists = db_get_field("SHOW COLUMNS FROM ?:user_profiles WHERE Field = ?s", $column_name);
        if (!$exists) {
            db_query("ALTER TABLE ?:user_profiles ADD COLUMN `{$column_name}` {$column_definition}");
        }
    }
}

/**
 * Map SPL region names to Saudi state codes used by the local cities dataset.
 */
function fn_is2or_spl_integration_map_region_to_state_code($region_name)
{
    $mapping = [
        'Riyadh' => '1',
        'Makkah' => '2',
        'Madinah' => '3',
        'Qassim' => '4',
        'Eastern Province' => '5',
        'Asir' => '6',
        'Tabuk' => '7',
        'Hail' => '8',
        'Northern Borders' => '9',
        'Jazan' => '10',
        'Najran' => '11',
        'Bahah' => '12',
        'Jawf' => '13',
    ];

    return $mapping[$region_name] ?? '';
}

/**
 * Resolve local cities.city_id from city/state/country text.
 */
function fn_is2or_spl_integration_resolve_city_id($city, $state_code, $country_code = 'SA', $lang_code = CART_LANGUAGE)
{
    if (empty($city) || empty($state_code) || empty($country_code) || !function_exists('fn_cities_get_city_ids')) {
        return 0;
    }

    $city = trim($city);
    $state_code = trim($state_code);
    $country_code = trim($country_code);
    $city_ids = fn_cities_get_city_ids($city, $state_code, $country_code, $lang_code);

    if (empty($city_ids) && defined('DESCR_SL') && DESCR_SL !== $lang_code) {
        $city_ids = fn_cities_get_city_ids($city, $state_code, $country_code, DESCR_SL);
    }

    return !empty($city_ids) ? (int) reset($city_ids) : 0;
}

/**
 * Generate full address from SPL short address
 */
function fn_is2or_spl_integration_generate_address($short_address, $language = 'E')
{
    fn_is2or_spl_integration_ensure_user_profile_city_id_columns();

    $api = new Api();
    list($result, $message) = $api->generateShortAddress($short_address, $language);

    if (!empty($result)) {
        foreach ($result as &$address) {
            $region_name = $address['RegionName_L2'] ?: $address['RegionName'];
            $state_code = fn_is2or_spl_integration_map_region_to_state_code($region_name);
            $city_name = $address['City_L2'] ?: $address['City'];
            $address['city_id'] = fn_is2or_spl_integration_resolve_city_id($city_name, $state_code, 'SA');
        }
        unset($address);
    }

    return [$result, $message];
}

/**
 * Hook: dipanggil saat addon status berubah (install/uninstall/enable/disable)
 */
function fn_settings_actions_addons_post_is2or_spl_integration($status)
{
    // Jika addon diaktifkan (baik install baru atau re-enable)
    if ($status == 'A') {
        fn_is2or_spl_integration_install();
    }
}

/**
 * Reorder profile fields dan tambah kolom SPL ke tabel companies
 */
function fn_is2or_spl_integration_install()
{
    // Reorder shipping section fields
    db_query("UPDATE ?:profile_fields SET position = 10 WHERE field_name = 's_firstname'");
    db_query("UPDATE ?:profile_fields SET position = 20 WHERE field_name = 's_lastname'");
    db_query("UPDATE ?:profile_fields SET position = 25 WHERE field_name = 's_spl_short_address'");
    db_query("UPDATE ?:profile_fields SET position = 30 WHERE field_name = 's_building_no'");
    db_query("UPDATE ?:profile_fields SET position = 40 WHERE field_name = 's_address'");
    db_query("UPDATE ?:profile_fields SET position = 50 WHERE field_name = 's_address_2'");
    db_query("UPDATE ?:profile_fields SET position = 60 WHERE field_name = 's_city'");
    db_query("UPDATE ?:profile_fields SET position = 70 WHERE field_name = 's_country'");
    db_query("UPDATE ?:profile_fields SET position = 80 WHERE field_name = 's_state'");
    db_query("UPDATE ?:profile_fields SET position = 90 WHERE field_name = 's_zipcode'");
    db_query("UPDATE ?:profile_fields SET position = 91 WHERE field_name = 's_coordinates'");
    db_query("UPDATE ?:profile_fields SET position = 100 WHERE field_name = 's_phone'");

    // Reorder billing section fields
    db_query("UPDATE ?:profile_fields SET position = 10 WHERE field_name = 'b_firstname'");
    db_query("UPDATE ?:profile_fields SET position = 20 WHERE field_name = 'b_lastname'");
    db_query("UPDATE ?:profile_fields SET position = 25 WHERE field_name = 'b_spl_short_address'");
    db_query("UPDATE ?:profile_fields SET position = 30 WHERE field_name = 'b_building_no'");
    db_query("UPDATE ?:profile_fields SET position = 40 WHERE field_name = 'b_address'");
    db_query("UPDATE ?:profile_fields SET position = 50 WHERE field_name = 'b_city'");
    db_query("UPDATE ?:profile_fields SET position = 60 WHERE field_name = 'b_country'");
    db_query("UPDATE ?:profile_fields SET position = 70 WHERE field_name = 'b_state'");
    db_query("UPDATE ?:profile_fields SET position = 80 WHERE field_name = 'b_zipcode'");
    db_query("UPDATE ?:profile_fields SET position = 81 WHERE field_name = 'b_coordinates'");
    db_query("UPDATE ?:profile_fields SET position = 90 WHERE field_name = 'b_phone'");

    // Add SPL columns to companies table - cek satu per satu
    $columns_to_add = [
        'spl_short_address' => "varchar(32) NOT NULL DEFAULT ''",
        'spl_building_no' => "varchar(16) NOT NULL DEFAULT ''",
        'spl_street' => "varchar(255) NOT NULL DEFAULT ''",
        'spl_district' => "varchar(255) NOT NULL DEFAULT ''",
        'spl_additional_number' => "varchar(16) NOT NULL DEFAULT ''"
    ];

    foreach ($columns_to_add as $column_name => $column_definition) {
        $exists = db_get_field("SHOW COLUMNS FROM ?:companies WHERE Field = ?s", $column_name);
        if (!$exists) {
            db_query("ALTER TABLE ?:companies ADD COLUMN `{$column_name}` {$column_definition}");
        }
    }

    // Add SPL verification columns to companies table
    $verification_columns = [
        'spl_verification_status'  => "varchar(16) NOT NULL DEFAULT 'pending'",
        'spl_verified_at'          => "int(11) unsigned NOT NULL DEFAULT 0",
        'spl_verification_message' => "varchar(255) NOT NULL DEFAULT ''",
        'spl_verified_by'          => "mediumint(8) unsigned NOT NULL DEFAULT 0",
    ];

    foreach ($verification_columns as $column_name => $column_definition) {
        $exists = db_get_field("SHOW COLUMNS FROM ?:companies WHERE Field = ?s", $column_name);
        if (!$exists) {
            db_query("ALTER TABLE ?:companies ADD COLUMN `{$column_name}` {$column_definition}");
        }
    }

    fn_is2or_spl_integration_ensure_user_profile_city_id_columns();
}

/**
 * Hook: assign building_no field IDs to view for JS access
 */
function fn_is2or_spl_integration_index_scripts_post()
{
    $view = Tygh::$app['view'];

    // Get building_no field IDs for JS auto-fill
    $s_building_no = db_get_field("SELECT field_id FROM ?:profile_fields WHERE field_name = 's_building_no'");
    $b_building_no = db_get_field("SELECT field_id FROM ?:profile_fields WHERE field_name = 'b_building_no'");

    $view->assign('is2or_spl_building_no_fields', [
        's' => (int) $s_building_no,
        'b' => (int) $b_building_no,
    ]);
}

/**
 * Hook: inject SPL data into TryOTO customer shipment request
 */
function fn_is2or_spl_integration_tryoto_create_shipment_data(&$data, &$order_info)
{
    if (empty($order_info['user_id'])) {
        return;
    }

    $user_id = $order_info['user_id'];
    $profile_id = $order_info['profile_id'] ?? 0;

    $spl_data = fn_is2or_spl_integration_get_user_spl_data($user_id, $profile_id);

    if (empty($spl_data)) {
        return;
    }

    // Remap customer address fields
    if (!empty($spl_data['lat'])) $data['customer']['lat'] = $spl_data['lat'];
    if (!empty($spl_data['lon'])) $data['customer']['lon'] = $spl_data['lon'];
    if (!empty($spl_data['address'])) $data['customer']['address'] = $spl_data['address'];
    if (!empty($spl_data['district'])) $data['customer']['district'] = $spl_data['district'];
    if (!empty($spl_data['city'])) $data['customer']['city'] = $spl_data['city'];
    if (!empty($spl_data['buildingNo'])) $data['customer']['buildingNo'] = $spl_data['buildingNo'];
    if (!empty($spl_data['street'])) $data['customer']['street'] = $spl_data['street'];
    if (!empty($spl_data['postcode'])) $data['customer']['postcode'] = $spl_data['postcode'];
    if (!empty($spl_data['shortAddressCode'])) $data['customer']['shortAddressCode'] = $spl_data['shortAddressCode'];
    if (!empty($spl_data['secondaryAddressNumber'])) $data['customer']['secondaryAddressNumber'] = $spl_data['secondaryAddressNumber'];
}

/**
 * Hook: inject SPL data into TryOTO vendor/sender shipment request
 */
function fn_is2or_spl_integration_tryoto_create_shipment_sender_data(&$data, &$order_info)
{
    if (empty($order_info['company_id'])) {
        return;
    }

    $company_id = $order_info['company_id'];
    $spl_data = fn_is2or_spl_integration_get_company_spl_data($company_id);

    if (empty($spl_data)) {
        return;
    }

    // Remap sender/vendor address fields
    if (!empty($spl_data['lat'])) $data['senderInformation']['lat'] = $spl_data['lat'];
    if (!empty($spl_data['lon'])) $data['senderInformation']['lon'] = $spl_data['lon'];
    if (!empty($spl_data['senderAddressLine'])) $data['senderInformation']['senderAddressLine'] = $spl_data['senderAddressLine'];
    if (!empty($spl_data['senderDistrict'])) $data['senderInformation']['senderDistrict'] = $spl_data['senderDistrict'];
    if (!empty($spl_data['senderCity'])) $data['senderInformation']['senderCity'] = $spl_data['senderCity'];
    if (!empty($spl_data['senderBuildingNo'])) $data['senderInformation']['senderBuildingNo'] = $spl_data['senderBuildingNo'];
    if (!empty($spl_data['senderStreet'])) $data['senderInformation']['senderStreet'] = $spl_data['senderStreet'];
    if (!empty($spl_data['senderPostcode'])) $data['senderInformation']['senderPostcode'] = $spl_data['senderPostcode'];
    if (!empty($spl_data['senderShortAddressCode'])) $data['senderInformation']['senderShortAddressCode'] = $spl_data['senderShortAddressCode'];
    if (!empty($spl_data['senderSecondaryAddressNumber'])) $data['senderInformation']['senderSecondaryAddressNumber'] = $spl_data['senderSecondaryAddressNumber'];
}

/**
 * Hook: inject SPL data into Torod customer order request
 */
function fn_is2or_spl_integration_torod_create_order_data(&$data, &$order_info)
{
    if (empty($order_info['user_id'])) {
        return;
    }

    $user_id = $order_info['user_id'];
    $profile_id = $order_info['profile_id'] ?? 0;

    $spl_data = fn_is2or_spl_integration_get_user_spl_data($user_id, $profile_id);

    if (empty($spl_data)) {
        return;
    }

    // Map SPL fields to Torod flat address structure
    if (!empty($spl_data['lat'])) $data['latitude'] = $spl_data['lat'];
    if (!empty($spl_data['lon'])) $data['longitude'] = $spl_data['lon'];
    if (!empty($spl_data['address'])) $data['locate_address'] = $spl_data['address'];
    if (!empty($spl_data['city'])) $data['city'] = $spl_data['city'];
    if (!empty($spl_data['city_id'])) $data['city_id'] = (int) $spl_data['city_id'];
    if (!empty($spl_data['district'])) $data['district'] = $spl_data['district'];
    if (!empty($spl_data['buildingNo'])) $data['building_no'] = $spl_data['buildingNo'];
    if (!empty($spl_data['street'])) $data['street'] = $spl_data['street'];
}

/**
 * Hook: inject SPL data into Torod shipment sender data
 */
function fn_is2or_spl_integration_torod_create_shipment_sender_data(&$data, &$order_info)
{
    if (empty($order_info['company_id'])) {
        return;
    }

    $company_id = $order_info['company_id'];
    $spl_data = fn_is2or_spl_integration_get_company_spl_data($company_id);

    if (empty($spl_data)) {
        return;
    }

    // Map SPL fields to Torod sender/warehouse structure
    if (!empty($spl_data['lat'])) $data['sender_latitude'] = $spl_data['lat'];
    if (!empty($spl_data['lon'])) $data['sender_longitude'] = $spl_data['lon'];
    if (!empty($spl_data['senderAddressLine'])) $data['sender_address'] = $spl_data['senderAddressLine'];
    if (!empty($spl_data['senderCity'])) $data['sender_city'] = $spl_data['senderCity'];
    if (!empty($spl_data['senderDistrict'])) $data['sender_district'] = $spl_data['senderDistrict'];
    if (!empty($spl_data['senderBuildingNo'])) $data['sender_building_no'] = $spl_data['senderBuildingNo'];
    if (!empty($spl_data['senderStreet'])) $data['sender_street'] = $spl_data['senderStreet'];
    if (!empty($spl_data['senderPostcode'])) $data['sender_postcode'] = $spl_data['senderPostcode'];
    if (!empty($spl_data['senderShortAddressCode'])) $data['sender_spl_short_address'] = $spl_data['senderShortAddressCode'];
}

/**
 * Get SPL data from user profile fields
 */
function fn_is2or_spl_integration_get_user_spl_data($user_id, $profile_id = 0)
{
    $user_info = fn_get_user_info($user_id, true, $profile_id);

    if (empty($user_info)) {
        return [];
    }

    $spl_field_id = db_get_field("SELECT field_id FROM ?:profile_fields WHERE field_name = 's_spl_short_address'");
    $spl_short_address = '';
    if ($spl_field_id && !empty($user_info['fields'][$spl_field_id])) {
        $spl_short_address = $user_info['fields'][$spl_field_id];
    }

    $building_field_id = db_get_field("SELECT field_id FROM ?:profile_fields WHERE field_name = 's_building_no'");
    $building_no = '';
    if ($building_field_id && !empty($user_info['fields'][$building_field_id])) {
        $building_no = $user_info['fields'][$building_field_id];
    }

    $lat = $user_info['s_latitude'] ?? '';
    $lon = $user_info['s_longitude'] ?? '';

    return [
        'lat'                    => $lat,
        'lon'                    => $lon,
        'address'                => trim(($user_info['s_address'] ?? '') . ' ' . ($user_info['s_address_2'] ?? '')),
        'district'               => '',
        'city'                   => $user_info['s_city'] ?? '',
        'city_id'                => (int) ($user_info['s_city_id'] ?? 0),
        'buildingNo'             => $building_no,
        'street'                 => $user_info['s_address'] ?? '',
        'postcode'               => $user_info['s_zipcode'] ?? '',
        'shortAddressCode'       => $spl_short_address,
        'secondaryAddressNumber' => '',
    ];
}

/**
 * Ensure city_id values are present on user profile saves.
 */
function fn_is2or_spl_integration_update_user_profile_pre($user_id, &$user_data, $action)
{
    fn_is2or_spl_integration_ensure_user_profile_city_id_columns();

    foreach (['s', 'b'] as $prefix) {
        $city_key = $prefix . '_city';
        $state_key = $prefix . '_state';
        $country_key = $prefix . '_country';
        $city_id_key = $prefix . '_city_id';

        if (!empty($user_data[$city_id_key])) {
            $user_data[$city_id_key] = (int) $user_data[$city_id_key];
            continue;
        }

        $user_data[$city_id_key] = fn_is2or_spl_integration_resolve_city_id(
            $user_data[$city_key] ?? '',
            $user_data[$state_key] ?? '',
            $user_data[$country_key] ?? 'SA'
        );
    }
}

/**
 * Ensure city_id values are present on user account saves.
 */
function fn_is2or_spl_integration_update_user_pre($user_id, &$user_data, $auth, $ship_to_another, $notify_user, &$can_update)
{
    fn_is2or_spl_integration_update_user_profile_pre($user_id, $user_data, '');
}

/**
 * Backfill existing profiles with persisted city_id values.
 */
function fn_is2or_spl_integration_update_user_profile_post($user_id, $user_data, $action)
{
    if (empty($user_data['profile_id'])) {
        return;
    }

    $update = [];
    foreach (['s', 'b'] as $prefix) {
        $city_id_key = $prefix . '_city_id';
        if (isset($user_data[$city_id_key])) {
            $update[$city_id_key] = (int) $user_data[$city_id_key];
        }
    }

    if (!empty($update)) {
        db_query('UPDATE ?:user_profiles SET ?u WHERE profile_id = ?i', $update, $user_data['profile_id']);
    }
}

/**
 * Persist resolved city IDs after full user updates as well.
 */
/**
 * Get SPL data from company
 */
function fn_is2or_spl_integration_get_company_spl_data($company_id)
{
    $company = db_get_row(
        "SELECT address, city, state, zipcode, latitude, longitude,
                spl_short_address, spl_building_no, spl_street, spl_district, spl_additional_number
         FROM ?:companies WHERE company_id = ?i",
        $company_id
    );

    if (empty($company)) {
        return [];
    }

    $address_line = trim(($company['spl_building_no'] ?? '') . ' ' . ($company['spl_street'] ?? '') . ', ' . ($company['address'] ?? ''));
    if (empty(trim($address_line, ' ,'))) {
        $address_line = $company['address'] ?? '';
    }

    return [
        'lat'                          => $company['latitude'] ?? '',
        'lon'                          => $company['longitude'] ?? '',
        'senderAddressLine'            => $address_line,
        'senderDistrict'               => $company['spl_district'] ?? '',
        'senderCity'                   => $company['city'] ?? '',
        'senderBuildingNo'             => $company['spl_building_no'] ?? '',
        'senderStreet'                 => $company['spl_street'] ?? '',
        'senderPostcode'               => $company['zipcode'] ?? '',
        'senderShortAddressCode'       => $company['spl_short_address'] ?? '',
        'senderSecondaryAddressNumber' => $company['spl_additional_number'] ?? '',
    ];
}

/**
 * Hook: save SPL company data on company update
 */
function fn_is2or_spl_integration_update_company_post($company_data, $company_id, $lang_code, $action)
{
    $spl_fields = [
        'spl_short_address',
        'spl_building_no',
        'spl_street',
        'spl_district',
        'spl_additional_number',
    ];

    $update = [];
    foreach ($spl_fields as $field) {
        if (isset($company_data[$field])) {
            $update[$field] = $company_data[$field];
        }
    }

    if (!empty($update)) {
        db_query("UPDATE ?:companies SET ?u WHERE company_id = ?i", $update, $company_id);
    }

    // Run SPL address verification after saving SPL fields
    fn_is2or_spl_integration_verify_company_address($company_data, $company_id, $lang_code, $action);
}

/**
 * Hook: verify company address against SPL after company save.
 *
 * @param array  $company_data Data submitted for company update
 * @param int    $company_id   Company ID
 * @param string $lang_code    Language code (unused, hook signature)
 * @param string $action       Action (unused, hook signature)
 */
function fn_is2or_spl_integration_verify_company_address($company_data, $company_id, $lang_code = '', $action = '')
{
    // Only run if we have something address-like to verify
    $has_spl_fields = !empty($company_data['spl_building_no'])
        && !empty($company_data['spl_additional_number']);

    $has_raw_address = !empty($company_data['zipcode'])
        && !empty($company_data['address']);

    if (!$has_spl_fields && !$has_raw_address) {
        return;
    }

    $api = new Api();

    $building_no   = $company_data['spl_building_no'] ?? '';
    $zip_code      = $company_data['zipcode'] ?? '';
    $additional_no = $company_data['spl_additional_number'] ?? '';

    // If SPL fields are populated (from Generate address), go straight to verify
    if ($building_no && $zip_code && $additional_no) {
        $result = $api->verifyAddress($building_no, $zip_code, $additional_no);
        _is2or_spl_integration_save_verification_result($company_id, $result);
        return;
    }

    // Fallback: try free text search to extract building/zip/additional
    $address_parts = array_filter([
        $company_data['spl_building_no'] ?? '',
        $company_data['address'] ?? '',
        $company_data['city'] ?? '',
        $company_data['zipcode'] ?? '',
    ]);
    $free_text = implode(' ', $address_parts);

    if (empty(trim($free_text))) {
        return;
    }

    $addresses = $api->freeTextSearch($free_text);

    if (empty($addresses)) {
        return;
    }

    // Use first result to verify
    $first = $addresses[0];
    $result = $api->verifyAddress(
        $first['BuildingNumber'] ?? '',
        $first['PostCode'] ?? '',
        $first['AdditionalNumber'] ?? ''
    );
    _is2or_spl_integration_save_verification_result($company_id, $result);
}

/**
 * Internal: save SPL verification result to ?:companies.
 */
function _is2or_spl_integration_save_verification_result($company_id, array $result)
{
    if (!empty($result['error'])) {
        $status = VerificationStatus::PENDING;
    } else {
        $status = $result['found'] ? VerificationStatus::VERIFIED : VerificationStatus::FAILED;
    }
    $message = $result['message'] ?? '';
    $now     = TIME;

    db_query(
        "UPDATE ?:companies
         SET spl_verification_status = ?s,
             spl_verified_at = ?i,
             spl_verification_message = ?s,
             spl_verified_by = 0
         WHERE company_id = ?i",
        $status, $now, $message, $company_id
    );
}

/**
 * Get SPL verification status for a company.
 *
 * @param  int   $company_id
 * @return array [status, verified_at, message, verified_by]
 */
function fn_is2or_spl_integration_get_verification_status($company_id)
{
    return db_get_row(
        "SELECT spl_verification_status AS status,
                spl_verified_at AS verified_at,
                spl_verification_message AS message,
                spl_verified_by AS verified_by
         FROM ?:companies WHERE company_id = ?i",
        $company_id
    ) ?: [
        'status'     => VerificationStatus::PENDING,
        'verified_at'=> 0,
        'message'    => '',
        'verified_by'=> 0,
    ];
}

/**
 * Admin override: mark company address as overridden.
 *
 * @param int $company_id
 * @param int $admin_user_id
 */
function fn_is2or_spl_integration_override_verification($company_id, $admin_user_id)
{
    db_query(
        "UPDATE ?:companies
         SET spl_verification_status = ?s,
             spl_verified_at = ?i,
             spl_verification_message = 'Manually overridden by admin',
             spl_verified_by = ?i
         WHERE company_id = ?i",
        VerificationStatus::OVERRIDDEN, TIME, $admin_user_id, $company_id
    );
}

/**
 * Admin clear override: revert to pending for re-verification.
 *
 * @param int $company_id
 */
function fn_is2or_spl_integration_clear_override($company_id)
{
    db_query(
        "UPDATE ?:companies
         SET spl_verification_status = ?s,
             spl_verified_at = 0,
             spl_verification_message = '',
             spl_verified_by = 0
         WHERE company_id = ?i",
        VerificationStatus::PENDING, $company_id
    );
}

/**
 * Hook: filter companies list to show SPL verification status.
 * Adds spl_verification_status to company list data for admin display.
 */
function fn_is2or_spl_integration_get_companies_list($condition, $pattern, $start, $limit, array &$params)
{
    // No-op — the columns are already on ?:companies and will be
    // returned by fn_get_companies() automatically.
    // This hook registration is a placeholder for v2 list filtering.
}

/**
 * Hook: gate vendor storefront visibility on SPL verification status.
 * Only active on storefront (AREA_C), not admin.
 */
function fn_is2or_spl_integration_get_company_data_post(&$company_data, $company_id, $lang_code, $params)
{
    if (AREA !== 'C' || empty($company_data)) {
        return;
    }

    $spl_status = $company_data['spl_verification_status'] ?? '';

    // If column doesn't exist yet (old data), don't block
    if ($spl_status === '' || in_array($spl_status, VerificationStatus::VISIBLE)) {
        return;
    }

    // Mark as not found — CS-Cart will show 404
    $company_data = [];
}