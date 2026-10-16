<?php

defined('BOOTSTRAP') or die('Access denied');

if (empty($auth['user_type']) || $auth['user_type'] !== 'A') {
    return [CONTROLLER_STATUS_DENIED];
}

if ($mode === 'clear') {
    if (fn_is2or_thumbnail_crop_clear_thumbnails()) {
        fn_set_notification('N', __('notice'), __('is2or_thumbnail_crop.text_thumbnails_cleared'));
    } else {
        fn_set_notification('E', __('error'), __('is2or_thumbnail_crop.text_thumbnails_clear_failed'));
    }

    return [CONTROLLER_STATUS_REDIRECT, 'addons.update?addon=is2or_thumbnail_crop'];
}

if ($mode === 'repair_settings') {
    if (fn_is2or_thumbnail_crop_repair_settings()) {
        fn_set_notification('N', __('notice'), __('is2or_thumbnail_crop.text_settings_repaired'));
    } else {
        fn_set_notification('E', __('error'), __('is2or_thumbnail_crop.text_settings_repair_failed'));
    }

    return [CONTROLLER_STATUS_REDIRECT, 'addons.update?addon=is2or_thumbnail_crop'];
}

return [CONTROLLER_STATUS_NO_PAGE];
