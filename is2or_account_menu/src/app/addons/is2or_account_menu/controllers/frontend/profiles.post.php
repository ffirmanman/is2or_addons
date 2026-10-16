<?php
/***************************************************************************
 *                                                                         *
 * Post-controller for "profiles" (frontend/storefront area).             *
 *                                                                         *
 ***************************************************************************/

if (!defined('AREA')) { die('Access denied'); }

// Only relevant for the profile view/edit page for a logged in user
if (empty($auth['user_id']) || !in_array($mode, array('update', 'add'))) {
    return;
}

$user_id = (int) $auth['user_id'];
$allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$profile_dir = DIR_ROOT . '/images/profiles/';

/**
 * Returns [path, ext] of the current (latest) profile picture file for this
 * user, if any. Files are named user_{id}_{timestamp}.{ext}, so there could
 * in theory be leftovers from a failed cleanup — we always pick the newest.
 */
$find_existing_image = function () use ($profile_dir, $user_id) {
    $matches = glob($profile_dir . 'user_' . $user_id . '_*.*');

    if (empty($matches)) {
        return null;
    }

    usort($matches, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $latest = $matches[0];
    $ext = strtolower(pathinfo($latest, PATHINFO_EXTENSION));

    return array($latest, $ext);
};

// --- 1. Handle a newly uploaded profile picture ---------------------------
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $mode === 'update'
    && !empty($_FILES['profile_picture'])
) {
    $file = $_FILES['profile_picture'];

    // Upload itself failed before reaching our validation (e.g. exceeded
    // upload_max_filesize / post_max_size in php.ini, or MAX_FILE_SIZE in
    // the form). Without this branch, the user gets no feedback at all.
    if ($file['error'] !== UPLOAD_ERR_OK) {

        fn_set_notification('E', __('error'), __('text_file_could_not_be_uploaded'));

    } else {

        $ext = strtolower(fn_get_file_ext($file['name']));
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate extension
        if (!in_array($ext, $allowed_exts)) {
            fn_set_notification('E', __('error'), __('text_not_allowed_to_upload_file_extension'));

        // Validate size
        } elseif ($file['size'] > $max_size) {
            fn_set_notification('E', __('error'), __('text_file_too_big'));

        // Validate that the uploaded file is actually a real image
        // (extension alone can be spoofed, e.g. renamed .php to .jpg)
        } elseif (@getimagesize($file['tmp_name']) === false) {
            fn_set_notification('E', __('error'), __('text_not_allowed_to_upload_file_extension'));

        } else {

            if (!is_dir($profile_dir)) {
                fn_mkdir($profile_dir);
            }

            if (!is_writable($profile_dir)) {

                fn_set_notification('E', __('error'), __('text_file_could_not_be_uploaded'));

            } else {

                // Unique filename per upload. This is the key fix: any CDN /
                // reverse proxy / browser cache keys its cache off the URL,
                // so re-using the same filename (user_98.png) means a stale
                // cached response can keep being served forever even after
                // the file on disk is replaced. A new filename per upload
                // guarantees a cache miss every time.
                $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $filepath = $profile_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {

                    // Clean up old files for this user so we don't
                    // accumulate orphans on every re-upload.
                    $old_files = glob($profile_dir . 'user_' . $user_id . '_*.*');

                    if ($old_files !== false) {
                        foreach ($old_files as $old_file) {
                            if ($old_file !== $filepath) {
                                fn_rm($old_file);
                            }
                        }
                    }

                    fn_set_notification('N', __('notice'), __('text_profile_image_updated'));

                } else {
                    fn_set_notification('E', __('error'), __('text_file_could_not_be_uploaded'));
                }
            }
        }
    }
}

// --- 2. Always (re)inject profile_image_url for the template --------------
// This runs on every GET *and* every POST back to this page, so the photo
// is correct right after saving as well as on a fresh page load.
$view = Tygh::$app['view'];
$user_data = (array) $view->getTemplateVars('user_data');

$existing = $find_existing_image();

if ($existing !== null) {
    list($found_path, $found_ext) = $existing;

    // Plain relative path, NOT fn_url(). fn_url() is meant for building
    // dispatch URLs (controller.mode) and can append unwanted params
    // (e.g. company_id on Multi-Vendor) when given a raw static path.
    // Because the filename itself is now unique per upload, we don't
    // need a manual cache-busting query string on top of it either.
    $user_data['profile_image_url'] = '/images/profiles/' . basename($found_path);
} else {
    unset($user_data['profile_image_url']);
}

$view->assign('user_data', $user_data);