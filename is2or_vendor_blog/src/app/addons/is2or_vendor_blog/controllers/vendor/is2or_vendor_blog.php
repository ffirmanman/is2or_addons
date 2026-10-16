<?php
/**
 * Vendor Blog - Vendor panel controller
 * Mirrors admin pages.manage (page_type=B) but restricted to vendor's own posts
 */

use Tygh\Registry;
use Tygh\Languages\Languages;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Only allow vendor access
if (defined('ACCOUNT_TYPE') && ACCOUNT_TYPE !== 'V') {
    fn_set_notification('E', __('error'), __('access_denied'));
    return array(CONTROLLER_STATUS_REDIRECT, 'is2or_vendor_blog.manage');
}

$company_id = Registry::get('runtime.company_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- UPDATE / SAVE ----------
    if ($mode === 'update') {
        $page_data = $_REQUEST['page_data'] ?? array();

        // Force company_id to current vendor — never trust user input
        $page_data['company_id'] = $company_id;
        $page_data['page_type']  = 'B'; // Blog

        if (!empty($page_data['page_id'])) {
            // Edit: verify ownership before saving
            $owner = db_get_field(
                "SELECT company_id FROM ?:pages WHERE page_id = ?i",
                $page_data['page_id']
            );
            if ((int)$owner !== (int)$company_id) {
                fn_set_notification('E', __('error'), __('access_denied'));
                return array(CONTROLLER_STATUS_REDIRECT, 'is2or_vendor_blog.manage');
            }
        }

        $page_id = fn_update_page($page_data, $page_data['page_id'] ?? 0, DESCR_SL);

        if ($page_id) {
            fn_set_notification('N', __('notice'), __('text_changes_saved'));
        }

        return array(CONTROLLER_STATUS_REDIRECT, "is2or_vendor_blog.update?page_id=$page_id");
    }

    // ---------- DELETE ----------
    if ($mode === 'm_delete' || $mode === 'delete') {
        $page_ids = !empty($_REQUEST['page_ids'])
            ? (array) $_REQUEST['page_ids']
            : (isset($_REQUEST['page_id']) ? array($_REQUEST['page_id']) : array());

        foreach ($page_ids as $page_id) {
            $owner = db_get_field(
                "SELECT company_id FROM ?:pages WHERE page_id = ?i",
                $page_id
            );
            if ((int)$owner === (int)$company_id) {
                fn_delete_page($page_id);
            }
        }

        fn_set_notification('N', __('notice'), __('text_changes_saved'));
        return array(CONTROLLER_STATUS_REDIRECT, 'is2or_vendor_blog.manage');
    }

    // ---------- CHANGE STATUS ----------
    if ($mode === 'update_status') {
        $page_id = (int) ($_REQUEST['id'] ?? 0);
        $status  = $_REQUEST['status'] ?? '';

        $owner = db_get_field(
            "SELECT company_id FROM ?:pages WHERE page_id = ?i",
            $page_id
        );
        if ((int)$owner === (int)$company_id) {
            db_query(
                "UPDATE ?:pages SET status = ?s WHERE page_id = ?i",
                $status,
                $page_id
            );
        }

        exit; // AJAX response
    }

    return array(CONTROLLER_STATUS_OK);
}

// ============================================================
// GET handlers
// ============================================================

// ---------- MANAGE (list) ----------
if ($mode === 'manage') {

    $params = array(
        'page_type'  => 'B',
        'company_id' => $company_id,
        'get_tree'   => 'multi_level',
    );

    // Allow simple search from the sidebar search box
    if (!empty($_REQUEST['q'])) {
        $params['q'] = $_REQUEST['q'];
    }

    list($pages, $search) = fn_get_pages($params, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign('pages', $pages);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('page_type', 'B');
    Tygh::$app['view']->assign('is2or_vendor_blog_mode', true);

    // Statuses used by the status dropdown (same as admin)
    Tygh::$app['view']->assign('statuses', fn_get_statuses(STATUSES_PAGE));

// ---------- UPDATE (add / edit form) ----------
} elseif ($mode === 'update') {

    $page_id = (int) ($_REQUEST['page_id'] ?? 0);

    if ($page_id) {
        // Verify ownership
        $owner = db_get_field(
            "SELECT company_id FROM ?:pages WHERE page_id = ?i",
            $page_id
        );
        if ((int)$owner !== (int)$company_id) {
            fn_set_notification('E', __('error'), __('access_denied'));
            return array(CONTROLLER_STATUS_REDIRECT, 'is2or_vendor_blog.manage');
        }

        $page_data = fn_get_page_data($page_id, DESCR_SL);
    } else {
        $page_data = array(
            'page_type'  => 'B',
            'status'     => 'A',
            'company_id' => $company_id,
            'parent_id'  => 0,
        );
    }

    // Parent pages: only Blog root pages owned by this vendor or global (company_id = 0)
    $parent_pages = db_get_array(
        "SELECT p.page_id, pd.page AS page_name
         FROM ?:pages AS p
         LEFT JOIN ?:page_descriptions AS pd
             ON pd.page_id = p.page_id AND pd.lang_code = ?s
         WHERE p.page_type = 'B'
           AND (p.company_id = ?i OR p.company_id = 0)
           AND p.page_id != ?i
         ORDER BY pd.page",
        DESCR_SL,
        $company_id,
        $page_id ?: 0
    );

    Tygh::$app['view']->assign('page_data', $page_data);
    Tygh::$app['view']->assign('parent_pages', $parent_pages);
    Tygh::$app['view']->assign('page_type', 'B');
    Tygh::$app['view']->assign('is2or_vendor_blog_mode', true);
    Tygh::$app['view']->assign('languages', Languages::getAll());

// ---------- PREVIEW ----------
} elseif ($mode === 'preview') {
    $page_id = (int) ($_REQUEST['page_id'] ?? 0);

    $owner = db_get_field(
        "SELECT company_id FROM ?:pages WHERE page_id = ?i",
        $page_id
    );
    if ((int)$owner !== (int)$company_id) {
        fn_set_notification('E', __('error'), __('access_denied'));
        return array(CONTROLLER_STATUS_REDIRECT, 'is2or_vendor_blog.manage');
    }

    $url = fn_url("pages.view?page_id=$page_id");
    fn_redirect($url);
}
