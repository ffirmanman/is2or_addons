<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Http;


function fn_is2or_changes_gather_additional_products_data_post($product_ids, $params, &$products, $auth, $lang_code)
{
    if (empty($products)) {
        return;
    }

    foreach ($products as $key => $product) {
        if (!array_key_exists('company_id', $product)) {
            fn_log_event('general', 'notice', [
                'category' => 'is2or_changes_debug',
                'message' => 'Trouble product. product_id=' . ($product['product_id'] ?? 'unknown'),
            ]);
            unset($products[$key]);
        }
    }
}


/**
 * Hook 1: Menangkap return_url saat popup login dimuat
 */
function fn_is2or_changes_before_dispatch(&$controller, &$mode, &$action, &$dispatch_extra, &$area)
{
    // Hanya berjalan di frontend (Area 'C')
    if ($area === 'C') {
        // Saat form login dipanggil (termasuk popup AJAX pada STEP 2)
        if ($controller === 'auth' && $mode === 'login_form') {
            
            // Tangkap return_url dan simpan ke session bawaan CS-Cart
            if (!empty($_REQUEST['return_url'])) {
                Tygh::$app['session']['is2or_return_url'] = $_REQUEST['return_url'];
            } elseif (!empty($_REQUEST['redirect_url'])) {
                Tygh::$app['session']['is2or_return_url'] = $_REQUEST['redirect_url'];
            }
        }
    }
}

/**
 * Hook 2: Mengalihkan tujuan redirect setelah registrasi
 */
function fn_is2or_changes_redirect(&$url)
{
    // Cek apakah tujuan sistem saat ini adalah halaman profile update
    $is_profile_target = strpos($url, 'profiles.update') !== false || strpos($url, 'profiles-update') !== false;
    
    // Cek apakah aksi sebelumnya adalah mendaftar akun baru (dispatch=profiles.add / mode=add)
    $is_register_action = (!empty($_REQUEST['dispatch']) && $_REQUEST['dispatch'] === 'profiles.add') 
                       || (!empty($_REQUEST['mode']) && $_REQUEST['mode'] === 'add');

    // Jika user baru selesai register dan akan dilempar ke profile.update
    if ($is_profile_target && $is_register_action) {
        
        // Cek apakah kita punya simpanan return_url dari popup login (STEP 2)
        if (!empty(Tygh::$app['session']['is2or_return_url'])) {
            
            // Ganti tujuan URL ke checkout
            $url = fn_url(Tygh::$app['session']['is2or_return_url']);
            
            // Bersihkan session agar tidak menyangkut di proses lain pada masa depan
            unset(Tygh::$app['session']['is2or_return_url']); 
        }
    }
}