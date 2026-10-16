<?php
/*******************************************************************************************
*   ___  _          ______                     _ _                _                        *
*  / _ \| |         | ___ \                   | (_)              | |              © 2026   *
* / /_\ | | _____  _| |_/ /_ __ __ _ _ __   __| |_ _ __   __ _   | |_ ___  __ _ _ __ ___   *
* |  _  | |/ _ \ \/ / ___ \ '__/ _` | '_ \ / _` | | '_ \ / _` |  | __/ _ \/ _` | '_ ` _ \  *
* | | | | |  __/>  <| |_/ / | | (_| | | | | (_| | | | | | (_| |  | ||  __/ (_| | | | | | | *
* \_| |_/_|\___/_/\_\____/|_|  \__,_|_| |_|\__,_|_|_| |_|\__, |  \___\___|\__,_|_| |_| |_| *
*                                                         __/ |                            *
*                                                        |___/                             *
* ---------------------------------------------------------------------------------------- *
* This is commercial software, only users who have purchased a valid license and accept    *
* to the terms of the License Agreement can install and use this program.                  *
* ---------------------------------------------------------------------------------------- *
* website: https://cs-cart.alexbranding.com                                                *
*   email: info@alexbranding.com                                                           *
*******************************************************************************************/
use Tygh\Addons\VendorRating\ServiceProvider;
use Tygh\BlockManager\Block;
use Tygh\Enum\Addons\Discussion\DiscussionObjectTypes;
use Tygh\Enum\Addons\Discussion\DiscussionTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Models\Company;
use Tygh\Registry;
use Tygh\Shippings\Shippings;
use Tygh\Languages\Languages;
use Tygh\Models\VendorPlan;
if (!defined('BOOTSTRAP')) {
die('Access denied');
}
function fn_abt__unitheme2_mv_fn_abt__ut2_check_versions_post(&$arr)
{
$arr['multivendor'] = fn_get_addon_version('abt__unitheme2_mv');
}
function fn_abt__ut2_mv_copy_layouts()
{
$edition = 'multivendor';
$layouts = glob(Registry::get('config.dir.themes_repository') . "abt__unitheme2/layouts/layouts_{$edition}_*.xml");
if (!empty($layouts)) {
foreach (['responsive', 'abt__unitheme2'] as $theme) {
foreach ($layouts as $layout) {
$dir = Registry::get('config.dir.design_frontend') . $theme . '/layouts/';
if (is_dir($dir)) {
fn_copy($layout, $dir);
}
}
}
}
}
function fn_abt__ut2_mv_remove_cscart_layouts()
{
$layouts = db_get_fields('SELECT name FROM ?:bm_layouts WHERE theme_name = \'abt__unitheme2\'');
if (empty($layouts)) {
foreach (['themes_repository', 'design_frontend'] as $dir) {
$ultimate_layouts = glob(Registry::get("config.dir.{$dir}") . 'abt__unitheme2/layouts/layouts_ultimate_*.xml');
foreach ($ultimate_layouts as $ultimate_layout) {
fn_rm($ultimate_layout);
}
}
}
}
function fn_abt__ut2_mv_install()
{
$objects = [
[
't' => '?:banners',
'i' => [
['n' => 'company_id', 'p' => 'int(11) NOT NULL DEFAULT \'0\''],
],
'indexes' => [
'company_id' => 'company_id',
],
],
[
't' => '?:vendor_plans',
'i' => [
['n' => 'abt__ut2_mv_banners_for_products', 'p' => 'int(1) NOT NULL DEFAULT \'0\''],
['n' => 'abt__ut2_mv_personal_design', 'p' => 'int(1) NOT NULL DEFAULT \'0\''],
['n' => 'abt__ut2_mv_vendor_home_page', 'p' => 'int(1) NOT NULL DEFAULT \'0\''],
['n' => 'abt__ut2_mv_allow_vendor_metadata', 'p' => 'char(1) NOT NULL DEFAULT \'N\''],
['n' => 'abt__ut2_mv_vendor_metadata_about', 'p' => 'char(1) NOT NULL DEFAULT \'N\''],
['n' => 'abt__ut2_mv_vendor_metadata_products', 'p' => 'char(1) NOT NULL DEFAULT \'N\''],
['n' => 'abt__ut2_mv_vendor_metadata_reviews', 'p' => 'char(1) NOT NULL DEFAULT \'N\''],
['n' => 'abt__ut2_mv_vendor_metadata_store', 'p' => 'char(1) NOT NULL DEFAULT \'N\''],
],
],
[
't' => '?:company_descriptions',
'i' => [
['n' => 'ab__short_description', 'p' => 'TEXT'],
['n' => 'social_links', 'p' => 'TEXT NOT NULL'],
],
],
[
't' => '?:companies',
'i' => [
['n' => 'personal_design', 'p' => 'TEXT NOT NULL'],
],
],
];
if (!empty($objects) && is_array($objects)) {
foreach ($objects as $o) {
$table_name = substr($o['t'], 2);
if (!db_has_table($table_name)) {
continue;
}
$fields = db_get_fields('DESCRIBE ' . $o['t']);
if (!empty($fields) && is_array($fields)) {
if (!empty($o['i']) && is_array($o['i'])) {
foreach ($o['i'] as $f) {
if (!in_array($f['n'], $fields)) {
db_query('ALTER TABLE ?p ADD ?p ?p', $o['t'], $f['n'], $f['p']);
if (!empty($f['add_sql']) && is_array($f['add_sql'])) {
foreach ($f['add_sql'] as $sql) {
db_query($sql);
}
}
}
}
}
if (!empty($o['indexes']) && is_array($o['indexes'])) {
foreach ($o['indexes'] as $index => $keys) {
$existing_indexes = db_get_array('SHOW INDEX FROM ?p WHERE key_name = ?s', $o['t'], $index);
if (empty($existing_indexes) && !empty($keys)) {
db_query('ALTER TABLE ?p ADD INDEX ?p (?p)', $o['t'], $index, $keys);
}
}
}
}
}
}
fn_abt__ut2_mv_migration_v4191a_v4181a();
}
function fn_abt__ut2_mv_migration_v4191a_v4181a()
{
$company_ids = db_get_fields(
'SELECT company_id FROM ?:companies'
);
if (!empty($company_ids)) {
$companies_with_exist_compact_logo_field = db_get_fields(
'SELECT company_id FROM ?:logos WHERE company_id > 0 AND type = ?s '
, "compact"
);
$logo_data = [
'layout_id' => 0,
'style_id' => '',
'type' => 'compact',
'storefront_id' => 0,
];
foreach ($company_ids as $company_id) {
if (!in_array($company_id, $companies_with_exist_compact_logo_field)) {
$logo_data['company_id'] = $company_id;
db_query('INSERT INTO ?:logos ?e', $logo_data);
}
}
}
}
function fn_abt__ut2_mv_is_vendor_store_available($company_id = null)
{
static $available = null;
$company_id = $company_id ?: fn_abt__ut2_mv_get_vendor_id();
if (is_null($available)) {
$available = false;
if (Registry::get('addons.vendor_plans.status') === 'A') {
$company = Company::model()->find($company_id);
$available = $company->plan->abt__ut2_mv_vendor_home_page;
}
}
return (bool)$available;
}
function fn_abt__ut2_mv_is_banners_for_product_available($company_id = null)
{
static $available = null;
$company_id = $company_id ?: fn_abt__ut2_mv_get_vendor_id();
if (is_null($available)) {
$available = false;
if (Registry::get('addons.vendor_plans.status') === 'A') {
$company = Company::model()->find($company_id);
$available = $company->plan->abt__ut2_mv_banners_for_products;
}
}
return (bool)$available;
}
function fn_abt__ut2_mv_is_personal_design_available($company_id = null)
{
$company_id = $company_id ?: fn_abt__ut2_mv_get_vendor_id();
$available = false;
if (Registry::get('addons.vendor_plans.status') === ObjectStatuses::ACTIVE) {
$company = Company::model()->find($company_id);
$available = $company->plan->abt__ut2_mv_personal_design;
}
return (bool)$available;
}
function fn_abt__ut2_mv_get_vendor_id()
{
return AREA === 'C' ? ($_REQUEST['company_id'] ?? false) : Tygh::$app['session']['auth']['company_id'];
}
function fn_abt__ut2_mv_get_allowed_blocks()
{
return fn_get_schema('abt__ut2_mv_blocks', 'blocks');
}
function fn_abt__ut2_mv_get_blocks()
{
$core_blocks = fn_get_schema('block_manager', 'blocks');
$allowed_blocks = fn_abt__ut2_mv_get_allowed_blocks();
return array_intersect_key($core_blocks, $allowed_blocks);
}
function fn_abt__ut2_check_block_permissions($request_variables)
{
$allow = fn_abt__ut2_mv_is_vendor_store_available();
if (!empty($request_variables['block_data']['block_id'])) {
$allow = ($allow && db_get_field('SELECT COUNT(*) FROM ?:bm_blocks WHERE block_id = ?i AND company_id = ?i', $request_variables['block_data']['block_id'], fn_abt__ut2_mv_get_vendor_id()));
}
if (isset($request_variables['block_data']['type'])) {
$allowed_blocks = fn_abt__ut2_mv_get_allowed_blocks();
$allow = ($allow && isset($allowed_blocks[$request_variables['block_data']['type']]));
}
return $allow;
}
function fn_abt__ut2_check_banner_permissions($request_variables)
{
$allow = fn_abt__ut2_mv_is_vendor_store_available();
if (!empty($request_variables['banner_id'])) {
$allow = $allow && db_get_field('SELECT COUNT(*) FROM ?:banners WHERE banner_id = ?i AND company_id = ?i', $request_variables['banner_id'], fn_abt__ut2_mv_get_vendor_id());
}
return $allow;
}
function fn_abt__unitheme2_mv_check_company_permissions(&$permission, $controller, $mode, $request_method, $request_variables, $extra, $schema)
{
if (isset($schema[$controller]['modes'][$mode]['sub_condition'])) {
$permission = $permission || call_user_func_array($schema[$controller]['modes'][$mode]['sub_condition']['function'], [$request_variables]);
}
}
function fn_abt__unitheme2_mv_update_block_post($block_data, $description, $block_id)
{
if (isset($_REQUEST['abt__ut2_mv_is_vendor_block']) && isset($block_data['company_id'])) {
$company_id = fn_abt__ut2_mv_get_vendor_id();
if ($block_data['company_id'] != $company_id) {
return;
}
$block_exists = (bool)db_get_field('SELECT COUNT(*) FROM ?:abt__ut2_mv_vendor_blocks WHERE company_id = ?i AND block_id = ?i', $company_id, $block_id);
if (!$block_exists) {
$last_postition = db_get_field('SELECT MAX(position) FROM ?:abt__ut2_mv_vendor_blocks WHERE company_id = ?i', $company_id);
$vendor_block_data = [
'company_id' => $company_id,
'block_id' => $block_id,
'position' => $last_postition + 1,
];
db_replace_into('abt__ut2_mv_vendor_blocks', $vendor_block_data);
}
}
}
function fn_abt__unitheme2_mv_get_blocks(&$params, $items_per_page, $lang_code, &$fields, &$sortings, &$conditions, &$joins)
{
if (!empty($params['abt__ut2_mv_is_vendor_block'])) {
$params['sort_by'] = 'position';
$sortings['position'] = 'abt__mv_vendor_blocks.position';
if (empty($conditions['company_id'])) {
$conditions['company_id'] = db_quote('bm_blocks.company_id = ?i', fn_abt__ut2_mv_get_vendor_id());
}
$fields['status'] = db_quote('abt__mv_vendor_blocks.status');
$fields['position'] = db_quote('abt__mv_vendor_blocks.position');
$joins[] = db_quote('LEFT JOIN ?:abt__ut2_mv_vendor_blocks abt__mv_vendor_blocks ON bm_blocks.block_id = abt__mv_vendor_blocks.block_id');
$conditions['abt__mv_vb_block_id'] = db_quote('abt__mv_vendor_blocks.block_id IS NOT NULL');
if (!empty($params['status'])) {
$conditions['abt__mv_vb_status'] = db_quote('abt__mv_vendor_blocks.status = ?s', $params['status']);
}
$excluded_block_types = [];
if (SiteArea::isAdmin(AREA) && UserTypes::isVendor(Tygh::$app['session']['auth']['user_type'])) {
if (
Registry::get('addons.blog.status') !== ObjectStatuses::ACTIVE ||
!fn_check_view_permissions('pages.manage', 'GET')
) {
$excluded_block_types[] = 'vendor_blog';
}
if (!fn_check_view_permissions('banners.manage', 'GET')) {
$excluded_block_types[] = 'banners';
}
}
if (!empty($excluded_block_types)) {
$conditions['abt__mv_vb_excluded_block_types'] = db_quote('bm_blocks.type NOT IN (?a)', $excluded_block_types);
}
}
}

function fn_abt__ut2_mv_delete_block($block_id)
{
db_query('DELETE FROM ?:abt__ut2_mv_vendor_blocks WHERE block_id = ?i', $block_id);
}
function fn_abt__unitheme2_mv_render_block_pre(&$block, $block_schema, $params, $block_content)
{
if (Tygh::$app['session']['is_render_vendor_block'] && !empty($block['properties']['show_title'])) {
if ($block['properties']['show_title'] === 'Y') {
$block['wrapper'] = $block['properties']['wrapper'];
}
}
}
function fn_abt__ut2_mv_store_banner_info($banner_id, $vendor_id)
{
db_query('UPDATE ?:banners SET company_id = ?i WHERE banner_id = ?i', $vendor_id, $banner_id);
}
function fn_abt__unitheme2_mv_get_banners(&$params, &$condition, $sorting, $limit, $lang_code, $fields)
{
if (!empty($params['company_id'])) {
$condition .= db_quote(' AND ?:banners.company_id = ?i', $params['company_id']);
} elseif (
(
Registry::get('runtime.controller') == 'companies'
|| Registry::get('runtime.controller') == 'banners'
)
&& $vendor_id = fn_abt__ut2_mv_get_vendor_id()
) {
$condition .= db_quote(' AND ?:banners.company_id = ?i', $vendor_id);
}
if (!empty($params['abt__ut2_mv_company_id'])) {
$prevent_get_banners_condition = ' AND 1 = 2';
if (fn_abt__ut2_mv_is_personal_design_available($params['abt__ut2_mv_company_id'])) {
$company_data = fn_get_company_data($params['abt__ut2_mv_company_id']);
if (!empty($company_data['personal_design']['banner_main_page'])) {
$params['item_ids'] = $company_data['personal_design']['banner_main_page'];
$prevent_get_banners_condition = '';
}
}
$condition .= $prevent_get_banners_condition;
}
}
function fn_abt__unitheme2_mv_get_products_before_select(&$params, $join, $condition, $u_condition, $inventory_join_cond, $sortings, $total, $items_per_page, $lang_code, $having)
{
if (!empty($params['abt__ut2_mv_vendor_products']) && $params['abt__ut2_mv_vendor_products'] == 'Y' && empty($params['company_id'])) {
$product = Tygh::$app['view']->getTemplateVars('product');
$params['company_id'] = $product['company_id'] ?? $_REQUEST['company_id'] ?? false;
}
}
function fn_abt__ut2_mv_update_vendor_banner($type, $banner_id, $company_id)
{
$data = [
'company_id' => $company_id,
'type' => $type,
'banner_id' => $banner_id,
];
db_replace_into('abt__ut2_mv_vendor_banners', $data);
}
function fn_abt__ut2_mv_get_vendor_banners($company_id)
{
return db_get_hash_array('SELECT type, banner_id FROM ?:abt__ut2_mv_vendor_banners WHERE company_id = ?i', 'type', $company_id);
}
function fn_abt__unitheme2_mv_get_products($params, &$fields, $sortings, $condition, $join, $sorting, $group_by, $lang_code, $having)
{
if (AREA === 'C' && in_array('companies', $params['extend'])) {
$fields['abt__ut2_mv_company_city'] = 'companies.city as abt__ut2_mv_company_city';
$fields['abt__ut2_mv_company_country'] = 'companies.country as abt__ut2_mv_company_country';
}
}
function fn_abt__unitheme2_mv_gather_additional_products_data_post($product_ids, $params, &$products, $auth, $lang_code)
{
    if ($products) {
        $company_ids = array_unique(array_filter(array_column($products, 'company_id')));
        if (!empty($company_ids)) {
            $vendors_info = fn_abt__ut2_mv_get_vendors_info($company_ids);
            foreach ($products as $key => &$product) {
                $company_id = $product['company_id'] ?? null;

                if (empty($company_id) || !isset($vendors_info[$company_id]) || !is_array($vendors_info[$company_id])) {
                    unset($products[$key]);
                    continue;
                }

                $product = array_merge($product, $vendors_info[$company_id]);
            }
            unset($product);
        }
    }
}

function fn_abt__ut2_mv_get_vendors_info($vendor_ids)
{
static $vendors_info = [];
foreach ($vendor_ids as $vendor_id) {
if (!isset($vendors_info[$vendor_id])) {
$vendor_info = &$vendors_info[$vendor_id];
$vendor_info['abt__ut2_mv_company_logos'] = fn_get_logos($vendor_id);
fn_set_hook('abt__ut2_mv_get_vendor_info', $vendor_info, $vendor_id);
}
}
return $vendors_info;
}
function fn_vendor_rating_abt__ut2_mv_get_vendor_info(&$vendor_info, $company_id)
{
$service = ServiceProvider::getVendorService();
$vendor_info['abt__ut2_mv_company_rating'] = $service->getRelativeRating($company_id);
}
function fn_abt__ut2_mv_blocks_get_vendor_info($value, $block)
{
$company_id = $_REQUEST['company_id'] ?? null;
$vendor_info = fn_blocks_get_vendor_info();
if ($company_id) {
if (Registry::get('addons.discussion.status') === 'A') {
$rating_statistics = db_get_hash_single_array('SELECT r.rating_value, COUNT(b.post_id) as total_items'
. ' FROM ?:discussion as a LEFT JOIN ?:discussion_posts as b'
. ' ON a.thread_id=b.thread_id LEFT JOIN ?:discussion_rating r'
. ' ON r.post_id = b.post_id'
. ' WHERE a.object_type = ?s AND b.status = ?s'
. ' AND a.object_id = ?i'
. ' GROUP BY r.rating_value', [
'rating_value',
'total_items'
], DISCUSSION_OBJECT_TYPE_COMPANY, 'A', $company_id);
$vendor_info['discussion']['posts_count'] = array_sum($rating_statistics);
if ($vendor_info['discussion']['posts_count']) {
$total = $vendor_info['discussion']['posts_count'];
$percentages = array_map(function ($value) use ($total) {
return floor(($value / $total) * 100);
}, $rating_statistics);
$remaining = 100 - array_sum($percentages);
$nonZeroCount = count(array_filter($rating_statistics));
if ($remaining > 0 && $nonZeroCount > 0) {
$increment = ceil($remaining / $nonZeroCount);
foreach ($rating_statistics as $key => $value) {
if ($value > 0) {
$percentages[$key] += $increment;
$remaining -= $increment;
if ($remaining <= 0) {
break;
}
}
}
}
$vendor_info['discussion']['statistic'] = $percentages;
}
}
$vendor_info['total_products'] = db_get_field('SELECT COUNT(*) FROM ?:products WHERE company_id = ?i', $company_id);
$vendor_info['duration'] = fn_abt__ut2_mv_format_duration(TIME - $vendor_info['timestamp']);
}
return $vendor_info;
}
function fn_abt__ut2_mv_format_duration($seconds)
{
$minutes = floor($seconds / 60);
$hours = floor($minutes / 60);
$days = floor($hours / 24);
$months = floor($days / 30);
$years = floor($months / 12);
$duration = [];
if ($years > 0) {
$duration['years'] = $years;
$duration['months'] = $months % 12;
} elseif ($months > 0) {
$duration['months'] = $months;
$duration['days'] = $days % 30;
} elseif ($days > 0) {
$duration['days'] = $days;
} else {
$duration['days'] = 1;
}
return $duration;
}
function fn_abt__unitheme2_mv_ajax_destruct_before_response($ajax, $text, $embedded_is_enabled)
{
if (!empty($_REQUEST['_action_context']) && $_REQUEST['_action_context'] === 'page_in_popup' && is_string($result_id = reset($ajax->result_ids))) {
$start_position = strpos($text, '<div class="tygh-content clearfix">');
$end_position = strpos($text, '<div class="tygh-footer clearfix" id="tygh_footer">');
if ($start_position && $end_position && $start_position < $end_position) {
$page_content = substr($text, $start_position, $end_position - $start_position);
$ajax->assignHtml($result_id, $page_content);
$ajax->assign('title', null);
}
}
}
function fn_abt__unitheme2_mv_get_company_data_post($company_id, $lang_code, $extra, &$company_data)
{
if (empty($company_data['company_id'])) {
return;
}
$company_data['social_links'] = json_decode($company_data['social_links'], true) ?? [];
if (fn_abt__ut2_mv_is_personal_design_available($company_id)) {
$company_data['personal_design'] = json_decode($company_data['personal_design'], true);
$company_data['personal_design']['abt__v_info_block_bg'] = fn_get_image_pairs($company_id, 'abt__v_info_block_bg', 'M');
} else {
unset($company_data['personal_design']);
}
$company_data['custom_metadata'] = fn_abt__ut2_get_company_custom_metadata($company_id, $lang_code);
}
function fn_abt__unitheme2_mv_get_banner_data($banner_id, $lang_code, &$fields)
{
$fields[] = '?:banners.company_id';
}
function fn_abt__unitheme2_mv_update_company_pre(&$company_data, $company_id, $lang_code, $can_update)
{
if (isset($company_data['personal_design'])) {
$company_data['personal_design'] = json_encode($company_data['personal_design']);
}
if (isset($company_data['social_links'])) {
$company_data['social_links'] = json_encode($company_data['social_links']);
}
}
function fn_abt__ut2_mv_get_vendor_menu($params)
{
if (empty($params['company_ids'])) {
return;
}
$items = [];
if (!empty($params['vendor_categories']) && $params['vendor_categories'] === 'Y') {
list($items['vendor_categories']) = fn_get_vendor_categories($params);
$items['vendor_categories'] = fn_abt__ut2_mv_form_objects_href($items['vendor_categories'], 'category_id', 'companies.products?company_id=' . $params['company_ids'] . '&category_id=', 'subcategories');
array_unshift($items['vendor_categories'], [
'category' => __('vendor_products'),
'href' => fn_url('companies.products?company_id=' . $params['company_ids'], 'C')
]);
}
if (!empty($params['pages']) && $params['pages'] === 'Y') {
$pages_params = [
'get_tree' => 'multi_level',
'company_id' => $params['company_ids'],
'simple' => true,
'status' => 'A',
];
list($items['pages']) = fn_get_pages($pages_params);
$items['pages'] = fn_abt__ut2_mv_form_objects_href($items['pages'], 'page_id', 'pages.view?page_id=', 'subpages');
}
return [$items];
}
function fn_abt__ut2_mv_form_objects_href($objects, $object_id_field, $url_pattern, $children_path)
{
foreach ($objects as &$object) {
$object['href'] = fn_url($url_pattern . $object[$object_id_field], 'C');
if (!empty($object[$children_path])) {
$object[$children_path] = fn_abt__ut2_mv_form_objects_href($object[$children_path], $object_id_field, $url_pattern, $children_path);
}
}
return $objects;
}
function fn_abt__unitheme2_mv_logo_types(&$types, $for_company)
{
if ($for_company) {
$types['compact'] = [
'for_layout' => true,
'text' => 'abt__ut2_mv.text_compact_logo',
'image' => '',
];
}
}
function fn_abt__unitheme2_mv_get_logos_post($company_id, $layout_id, $style_id, &$logos)
{
if (AREA == 'C' && !empty($company_id) && empty($logos['compact']['image'])) {
$logos['compact']['image'] = $logos['theme']['image'];
}
}
function fn_abt__unitheme2_mv_get_products_post(&$products, $params)
{
if (Registry::get('addons.vendor_plans.status') === 'A' && AREA == 'C') {
$vendor_store_availabilities = db_get_hash_single_array('
SELECT
c.company_id,
vp.vendor_store
FROM ?:companies as c
LEFT JOIN ?:vendor_plans as vp ON vp.plan_id = c.plan_id
', ['company_id', 'vendor_store']);
foreach ($products as &$product) {
if (!empty($product['company_id'])) {
$product['vendor_store_available'] = boolval($vendor_store_availabilities[$product['company_id']]);
}
}
}
if (!empty($params['is_vendor_products_list'])) {
$default_payments = fn_get_payments();
foreach ($products as &$product) {
$shippings = fn_get_available_shippings($product['company_id']);
$payments = fn_get_payments(['company_ids' => [$product['company_id']]]);
$product['company_data'] = [
'shippings' => $shippings,
'payments' => !empty($payments) ? $payments : $default_payments,
];
}
}
if (in_array('companies', $params['extend']) && AREA == 'C') {
foreach ($products as &$product) {
$product['company_name'] = fn_get_company_name($product['company_id'], '', DESCR_SL, ['use_i18n_fields' => true]);
}
}
}

function fn__abt__ut2_mv_is_company_reviews_available($company_id = null, $check_reviews_count = YesNo::NO)
{
static $availability = [];
$company_id = $company_id ?? (int)fn_abt__ut2_mv_get_vendor_id();
if (!isset($availability[$company_id])) {
$availability[$company_id] = Registry::get('addons.discussion.status') === ObjectStatuses::ACTIVE;
if ($availability[$company_id]) {
$discussion = fn_get_discussion($company_id, DiscussionObjectTypes::COMPANY, YesNo::isTrue($check_reviews_count));
$availability[$company_id] = $discussion['type'] !== DiscussionTypes::TYPE_DISABLED;
if ($availability[$company_id] && YesNo::isTrue($check_reviews_count)) {
$availability[$company_id] = count($discussion['posts']);
}
}
}
return $availability[$company_id];
}

function fn__abt__ut2_mv_get_company_microstore_homepage_blocks($company_id = null, $params = [])
{
$company_id = $company_id ?? (int)fn_abt__ut2_mv_get_vendor_id();
$params = array_merge([
'abt__ut2_mv_is_vendor_block' => true
], $params);
ksort($params);
$hash = md5($company_id . serialize($params));
static $blocks = [];
if (!isset($blocks[$hash])) {
list($blocks[$hash]) = Block::instance($company_id)->find($params, 0, DESCR_SL);
}
return $blocks[$hash];
}
function fn_abt__unitheme2_mv_url_post(&$_url, $area, $url, $protocol, $company_id_in_url, $lang_code, $locations, $storefront_id)
{
if (Registry::get('addons.seo.status') == 'A') {
parse_str(parse_url($_url, PHP_URL_QUERY) ?? '', $params);
if (
AREA == 'C'
&& !empty($params['dispatch'])
&& in_array($params['dispatch'], [
'companies.products',
'companies.home',
'companies.reviews'
])
) {
$dispatch = $params['dispatch'];
unset($params['dispatch']);
unset($params['company_id']);
$company_path_params = [
'company_id' => $company_id_in_url,
];
$cart_language = fn_abt__ut2_mv_get_cart_language_before_init($params, 'C');
if (Registry::get('addons.seo.single_url') == 'N' || Registry::get('addons.seo.seo_language') == 'Y') {
$company_path_params['sl'] = $params['sl'] ?? $cart_language ?? $lang_code;
unset($params['sl']);
}
$company_query_string = http_build_query($company_path_params);
$company_url = fn_url("companies.view?{$company_query_string}", 'C', 'current', $params['sl'] ?? $cart_language ?? $lang_code);
if ($dispatch == 'companies.products') {
$category_path = '';
$page_path = '';
$page = $params['page'] ?? '';
unset($params['page']);
if (!empty($params['category_id'])) {
$category_path_params = [
'category_id' => $params['category_id'],
'page' => $page,
];
if (Registry::get('addons.seo.single_url') == 'N') {
$category_path_params['sl'] = $params['sl'] ?? $cart_language ?? $lang_code;
}
$category_query_string = http_build_query($category_path_params);
$category_url = fn_url("categories.view?{$category_query_string}", 'C', 'current', $params['sl'] ?? $cart_language ?? $lang_code);
$storefront_url = fn_get_storefront_url('current', Registry::get('runtime.storefront_id'));
$category_path = str_replace($storefront_url, '', $category_url);
$category_path = rtrim($category_path, '/');
if (fn_abt__ut2_is_exist_seo_lang_code_in_url($params['sl'] ?? $cart_language, $storefront_id, $company_id_in_url)) {
$category_path = substr($category_path, 3);
}
unset($params['category_id']);
} elseif (!empty($page)) {
$page_path = '/page-' . $page;
}
$query_string = http_build_query($params);
if (!empty($query_string)) {
$query_string = '?' . $query_string;
}
if (!empty($params['sl']) && $cart_language == $params['sl']) {
unset($params['sl']);
}
$_url = $company_url . 'products' . $category_path . $page_path . '/' . $query_string;
} elseif ($dispatch == 'companies.home') {
$query_string = http_build_query($params);
if (!empty($query_string)) {
$query_string = '?' . $query_string;
}
if (!empty($params['sl']) && $cart_language == $params['sl']) {
unset($params['sl']);
}
$_url = $company_url . 'home/' . $query_string;
} elseif ($dispatch == 'companies.reviews') {
$page_path = '';
if (!empty($params['page'])) {
if ($params['page'] > 1) {
$page_path = '/page-' . $params['page'];
}
unset($params['page']);
}
if (!empty($params['selected_section'])) {
unset($params['selected_section']);
}
$query_string = http_build_query($params);
if (!empty($query_string)) {
$query_string = '?' . $query_string;
}
if (!empty($params['sl']) && $cart_language == $params['sl']) {
unset($params['sl']);
}
$_url = $company_url . 'reviews' . $page_path . '/' . $query_string;
}
}
}
}
function fn_abt__unitheme2_mv_get_route(&$req, &$result, $area, &$is_allowed_url)
{
if (Registry::get('addons.seo.status') == 'A') {
$request_uri = $_SERVER['REQUEST_URI'];
if (!empty($request_uri)
&& !empty($req['dispatch'])
&& $req['dispatch'] === '_no_page'
&& $area === 'C') {
parse_str(parse_url($request_uri, PHP_URL_QUERY), $params);
$storefront_url = fn_get_storefront_url('current', Registry::get('runtime.storefront_id'));
$parsed = parse_url($storefront_url);
$prefix = $parsed['path'];
$path = str_replace($prefix, '', $request_uri);
$uri_parts = explode('/', trim($path, '/'));
if (Registry::get('addons.seo.seo_language') == 'Y') {
$available_languages = array_keys(Languages::getSimpleLanguages());
if (in_array($uri_parts[0], $available_languages)) {
$lang_code_from_url = array_shift($uri_parts);
}
}
if (!empty($uri_parts[0]) && !empty($uri_parts[1]) && in_array($uri_parts[1], [
'products',
'home',
'reviews'
])) {
$vendor_name = $uri_parts[0];
$company_data = db_get_row('SELECT object_id,lang_code FROM ?:seo_names WHERE type = "m" AND name = ?s', $vendor_name);
if (empty($company_data)) {
if ($company_data = db_get_row('SELECT * FROM ?:seo_redirects WHERE type = "m" AND src = ?s', '/' . $vendor_name)) {
$redirect = true;
}
}
if (!empty($company_data)) {
if ($uri_parts[1] == 'products') {
unset($uri_parts[0]);
unset($uri_parts[1]);
foreach ($uri_parts as $key => $uri_part) {
if (str_contains($uri_part, 'page-')) {
$req['page'] = str_replace('page-', '', $uri_part);
unset($uri_parts[$key]);
}
if (str_contains($uri_part, '?')) {
unset($uri_parts[$key]);
}
}
if (!empty($uri_parts)) {
$category_seo_name = array_pop($uri_parts);
$category_id = db_get_field('SELECT object_id FROM ?:seo_names WHERE type = ?s AND name = ?s', 'c', $category_seo_name);
$req['category_id'] = $category_id;
}
$req['dispatch'] = 'companies.products';
}
elseif ($uri_parts[1] == 'home') {
unset($uri_parts[0]);
unset($uri_parts[1]);
$req['dispatch'] = 'companies.home';
}
elseif ($uri_parts[1] == 'reviews') {
foreach ($uri_parts as $key => $uri_part) {
if (str_contains($uri_part, 'page-')) {
$req['page'] = str_replace('page-', '', $uri_part);
unset($uri_parts[$key]);
}
if (str_contains($uri_part, '?')) {
unset($uri_parts[$key]);
}
}
unset($uri_parts[0]);
unset($uri_parts[1]);
$req['dispatch'] = 'companies.reviews';
}
$sl = $company_data['lang_code'];
if (Registry::get('addons.seo.seo_language') == 'Y') {
if (!empty($lang_code_from_url)) {
$sl = $lang_code_from_url;
} else {
$sl = Registry::get('settings.Appearance.' . fn_get_area_name($area) . '_default_language');
}
} elseif (Registry::get('addons.seo.single_url') == 'Y') {
$sl = fn_abt__ut2_mv_get_cart_language_before_init($req, $area);;
}
$req['sl'] = $sl;
$req['company_id'] = $company_data['object_id'];
$req = array_merge($req, $params);
$is_allowed_url = true;
$_SERVER['X-SEO-REWRITE'] = true;
}
if (!empty($redirect)) {
$result = [
INIT_STATUS_REDIRECT,
fn_generate_seo_url_from_schema($company_data, true, $req),
false,
true,
];
}
}
}
}
}
function fn_abt__ut2_mv_get_cart_language_before_init($params, $area)
{
$default_language = Registry::get('settings.Appearance.' . fn_get_area_name($area) . '_default_language');
$session_display_language = fn_get_session_data('cart_language' . $area);
$languages_search_params = [
'area' => $area,
'include_hidden' => $area !== 'C',
];
$avail_languages = Registry::getOrSetCache(
['init_language', 'init_language_' . $area],
['languages', 'storefronts_languages'],
['static', 'storefront'],
static function () use ($languages_search_params) {
return Languages::getAvailable($languages_search_params);
}
);
if (
!empty($params['sl'])
&& (
!empty($avail_languages[$params['sl']])
|| SiteArea::isAdmin($area)
)
) {
$display_language = $params['sl'];
} elseif ($session_display_language && !empty($avail_languages[$session_display_language])) {
$display_language = $session_display_language;
} elseif ($browser_language = fn_get_browser_language($avail_languages)) {
$display_language = $browser_language;
} elseif (!empty($avail_languages[$default_language])) {
$display_language = $default_language;
} else {
reset($avail_languages);
$display_language = key($avail_languages);
}
return $display_language;
}
function fn_abt__ut2_is_exist_seo_lang_code_in_url($lang_code, $storefront_id, $company_id = 0)
{
$seo_settings = fn_get_seo_settings($company_id, $storefront_id);
$show_secondary_language_in_uri = YesNo::toBool($seo_settings['seo_language']);
$show_single_url = YesNo::toBool($seo_settings['single_url']);
$default_frontend_language = $show_single_url ? $seo_settings['base_frontend_default_language'] : $seo_settings['frontend_default_language'];
return $show_secondary_language_in_uri && $lang_code !== $default_frontend_language;
}
function fn_abt__ut2_mv_get_company_meta($company_data, $page)
{
$title = $description = $keywords = '';
if (!in_array($page, ['about', 'products', 'reviews', 'store'])) {
return [$title, $description, $keywords];
}

$storefront_repository = Tygh::$app['storefront.repository'];
$current_storefront = $storefront_repository->findById(Registry::get('runtime.storefront_id'))->toArray();
$custom_metadata = [];
if (Registry::get('addons.vendor_plans.status') == 'A') {
$vendor_plan = VendorPlan::model()->find($company_data['plan_id']);
$property = "abt__ut2_mv_vendor_metadata_{$page}";
if ($vendor_plan->abt__ut2_mv_allow_vendor_metadata == 'Y' && $vendor_plan->{$property} == 'Y') {
$custom_metadata = $company_data['custom_metadata'][$page] ?? '';
}
}
$ut2_settings = fn_get_abt__ut2_settings();
$page_meta['title'] = !empty($custom_metadata['title']) ? $custom_metadata['title'] : $ut2_settings['vendor']["vendor_{$page}_page_metadata"]["vendor_{$page}_page_title"];
$page_meta['description'] = !empty($custom_metadata['description']) ? $custom_metadata['description'] : $ut2_settings['vendor']["vendor_{$page}_page_metadata"]["vendor_{$page}_page_description"];
$page_meta['keywords'] = !empty($custom_metadata['keywords']) ? $custom_metadata['keywords'] : $ut2_settings['vendor']["vendor_{$page}_page_metadata"]["vendor_{$page}_page_keywords"];
$title = str_replace('[company_name]', $company_data['company'], $page_meta['title']);
$title = str_replace('[storefront_name]', $current_storefront['name'], $title);
$description = str_replace('[company_name]', $company_data['company'], $page_meta['description']);
$description = str_replace('[storefront_name]', $current_storefront['name'], $description);
$keywords = str_replace('[company_name]', $company_data['company'], $page_meta['keywords']);
$keywords = str_replace('[storefront_name]', $current_storefront['name'], $keywords);
return [$title, $description, $keywords];
}
function fn_abt__unitheme2_mv_install_addon_post($addon)
{
if ($addon == 'vendor_plans') {
if (db_has_table('vendor_plans')) {
$columns = db_get_fields('DESCRIBE ?:vendor_plans');
$add_columns = [
'abt__ut2_mv_allow_vendor_metadata',
'abt__ut2_mv_vendor_metadata_about',
'abt__ut2_mv_vendor_metadata_products',
'abt__ut2_mv_vendor_metadata_reviews',
'abt__ut2_mv_vendor_metadata_store',
];
foreach ($add_columns as $column) {
if (!in_array($column, $columns)) {
db_query("ALTER TABLE ?p ADD ?p char(1) NOT NULL DEFAULT 'N'", "?:vendor_plans", $column);
}
}
}
}
}
function fn_abt__ut2_update_custom_vendor_metadata($params)
{
if (!empty($params['abt__ut2_mv_metadata']) && !empty($params['company_id'])) {
foreach ($params['abt__ut2_mv_metadata'] as $page => $page_metadata) {
foreach ($page_metadata as $setting => $value) {
$data = [
'company_id' => $params['company_id'],
'lang_code' => DESCR_SL,
'page' => $page,
'name' => $setting,
'value' => $value,
];
db_query('REPLACE INTO ?:abt__ut2_mv_vendor_custom_metadata ?e', $data);
}
}
}
}
function fn_abt__ut2_get_company_custom_metadata($company_id, $lang_code)
{
$formatted_vendor_custom_metadata = [];
$data = db_get_array('SELECT * FROM ?:abt__ut2_mv_vendor_custom_metadata WHERE company_id = ?i AND lang_code = ?s', $company_id, $lang_code);
if (!empty($data)) {
foreach ($data as $metadata) {
$formatted_vendor_custom_metadata[$metadata['page']][$metadata['name']] = $metadata['value'];
}
}
return $formatted_vendor_custom_metadata;
}