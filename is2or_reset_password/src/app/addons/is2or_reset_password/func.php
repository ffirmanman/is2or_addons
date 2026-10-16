<?php
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\ObjectStatuses;

defined('BOOTSTRAP') or die('Access denied');

function fn_is2or_reset_password_before_dispatch(&$controller, &$mode, &$action, &$dispatch_extra, $area)
{
    if (!SiteArea::isStorefront(AREA)) {
        return;
    }

    if ($controller !== 'auth' || $mode !== 'recover_password') {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['ekey'])) {
        Tygh::$app['session']['is2or_pending_ekey'] = $_GET['ekey'];

        // DEBUG: cek langsung setelah di-set, apakah benar tersimpan
        $check = Tygh::$app['session']['is2or_pending_ekey'] ?? 'FAILED_TO_SET';
        file_put_contents(
            __DIR__ . '/debug.log',
            json_encode([
                'time' => date('Y-m-d H:i:s'),
                'action' => 'SET_ON_GET',
                'ekey_set' => $_GET['ekey'],
                'verify_immediately' => $check,
                'session_id' => session_id(),
            ], JSON_PRETTY_PRINT) . "\n---\n",
            FILE_APPEND
        );

        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $session_ekey = Tygh::$app['session']['is2or_pending_ekey'] ?? 'NOT IN SESSION';

        // DEBUG: cek session saat POST
        file_put_contents(
            __DIR__ . '/debug.log',
            json_encode([
                'time' => date('Y-m-d H:i:s'),
                'action' => 'READ_ON_POST',
                'session_ekey' => $session_ekey,
                'post_ekey' => $_POST['ekey'] ?? 'NOT FOUND',
                'session_id' => session_id(),
            ], JSON_PRETTY_PRINT) . "\n---\n",
            FILE_APPEND
        );

        $ekey = !empty(Tygh::$app['session']['is2or_pending_ekey'])
            ? Tygh::$app['session']['is2or_pending_ekey']
            : ($_REQUEST['ekey'] ?? '');

        if (empty($ekey)) {
            return;
        }

        $validated = fn_is2or_validate_recovery_ekey($ekey);

        if ($validated === false || $validated === null) {
            unset(Tygh::$app['session']['is2or_pending_ekey']);
            return;
        }

        list($user_id) = $validated;

        unset(Tygh::$app['session']['is2or_pending_ekey']);

        Tygh::$app['session']['is2or_reset'] = [
            'user_id' => (int) $user_id,
            'active'  => true,
        ];

        fn_redirect(fn_url('is2or_reset_password.reset'));
        exit;
    }
}

function fn_is2or_validate_recovery_ekey($ekey)
{
    if (empty($ekey)) {
        return false;
    }

    $user_id = fn_get_object_by_ekey($ekey, RECOVERY_PASSWORD_EKEY_TYPE, false);
    if (empty($user_id)) {
        return false;
    }

    $user_info = fn_get_user_short_info($user_id);
    if (empty($user_info)) {
        return null;
    }

    $is_correct_area = (
        (UserTypes::isCustomer($user_info['user_type']) && SiteArea::isStorefront(AREA))
        || (UserTypes::isAdmin($user_info['user_type']) && SiteArea::isAdmin(AREA) && ACCOUNT_TYPE === 'admin')
        || (UserTypes::isVendor($user_info['user_type']) && SiteArea::isAdmin(AREA) && ACCOUNT_TYPE === 'vendor')
    );

    if (!$is_correct_area) {
        return false;
    }

    $user_data = fn_get_user_info($user_id, false);
    if (
        empty($user_data)
        || (isset($user_data['status']) && $user_data['status'] === ObjectStatuses::DISABLED)
    ) {
        return null;
    }

    fn_delete_ekey($ekey, RECOVERY_PASSWORD_EKEY_TYPE);

    return [(int) $user_id];
}