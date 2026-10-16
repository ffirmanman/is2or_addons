<?php

declare(strict_types=1);

$addon_root = dirname(__DIR__);
$addon_xml_path = $addon_root . '/src/app/addons/is2or_thumbnail_crop/addon.xml';
$init_path = $addon_root . '/src/app/addons/is2or_thumbnail_crop/init.php';
$lang_path = $addon_root . '/src/var/langs/en/addons/is2or_thumbnail_crop.po';
$func_path = $addon_root . '/src/app/addons/is2or_thumbnail_crop/func.php';
$controller_path = $addon_root . '/src/app/addons/is2or_thumbnail_crop/controllers/backend/is2or_thumbnail_crop.php';
$template_path = $addon_root . '/src/design/backend/templates/addons/is2or_thumbnail_crop/hooks/addons/manage_tools.post.tpl';

$addon_xml = file_get_contents($addon_xml_path);
$init_source = file_get_contents($init_path);
$lang_source = file_get_contents($lang_path);
$func_source = file_get_contents($func_path);
$controller_source = file_get_contents($controller_path);
$template_source = file_get_contents($template_path);

if ($addon_xml === false || $init_source === false || $lang_source === false) {
    fwrite(STDERR, "Failed to load thumbnail crop addon scaffold files.\n");
    exit(1);
}

$errors = [];

if (strpos($addon_xml, '<id>is2or_thumbnail_crop</id>') === false) {
    $errors[] = 'Addon XML must declare the is2or_thumbnail_crop addon id.';
}

if (strpos($addon_xml, '<sections>') === false || strpos($addon_xml, '<items>') === false) {
    $errors[] = 'Addon XML must wrap settings in <sections> and <items> so CS-Cart imports them.';
}

if (strpos($addon_xml, '<item id="outbound_object_types">') === false) {
    $errors[] = 'Addon XML must declare the outbound_object_types setting.';
}

if (strpos($addon_xml, '<type>multiple checkboxes</type>') === false) {
    $errors[] = 'Addon XML must use the CS-Cart multiple checkboxes setting type.';
}

if (strpos($addon_xml, '<default_value>product,detailed</default_value>') === false) {
    $errors[] = 'Addon XML must default outbound_object_types to product,detailed.';
}

if (strpos($addon_xml, '<priority>3500</priority>') === false) {
    $errors[] = 'Addon XML must set priority 3500 so the crop hook runs after watermarks priority 3000.';
}

if (strpos($init_source, 'fn_register_hooks(') === false || strpos($init_source, "'generate_thumbnail_post'") === false) {
    $errors[] = 'init.php must register the generate_thumbnail_post hook.';
}

if (strpos($lang_source, 'Addons::name::is2or_thumbnail_crop') === false) {
    $errors[] = 'Language file must define the addon name translation.';
}

if (strpos($lang_source, 'is2or_thumbnail_crop.repair_settings') === false) {
    $errors[] = 'Language file must define the repair-settings action label.';
}

if (strpos($lang_source, 'is2or_thumbnail_crop.text_settings_repaired') === false) {
    $errors[] = 'Language file must include success copy for the repair-settings action.';
}

if (strpos($lang_source, 'is2or_thumbnail_crop.text_settings_repair_failed') === false) {
    $errors[] = 'Language file must include failure copy for the repair-settings action.';
}

if (strpos($lang_source, 'is2or_thumbnail_crop.text_thumbnails_cleared') === false) {
    $errors[] = 'Language file must include success copy for the clear-thumbnails action.';
}

if (strpos($lang_source, 'is2or_thumbnail_crop.clear_thumbnails') === false) {
    $errors[] = 'Language file must define the clear-thumbnails action label.';
}

if ($func_source === false) {
    fwrite(STDERR, "Failed to load thumbnail crop addon func.php.\n");
    exit(1);
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_get_object_type_from_path') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_get_object_type_from_path.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_get_enabled_object_types') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_get_enabled_object_types.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_is_enabled_for_type') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_is_enabled_for_type.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_clear_thumbnails') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_clear_thumbnails.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_repair_settings') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_repair_settings.';
}

if (strpos($func_source, "return fn_reinstall_addon_files('is2or_thumbnail_crop');") === false) {
    $errors[] = 'func.php must repair settings via fn_reinstall_addon_files(\'is2or_thumbnail_crop\').';
}

if (strpos($func_source, "['product', 'detailed']") === false) {
    $errors[] = 'func.php must provide a product+detailed fallback when settings are missing.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_generate_outbound_contents') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_generate_outbound_contents.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_crop_to_visible_bounds') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_crop_to_visible_bounds.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_get_visible_bounds') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_get_visible_bounds.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_detect_visible_bounds_from_blob') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_detect_visible_bounds_from_blob.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_detect_visible_bounds_from_resource') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_detect_visible_bounds_from_resource.';
}

if (strpos($func_source, 'fn_is2or_thumbnail_crop_crop_to_visible_bounds($image, $bg_color)') === false) {
    $errors[] = 'func.php must trim visible bounds before generating the outbound thumbnail.';
}

if (strpos($func_source, 'THUMBNAIL_OUTBOUND') === false) {
    $errors[] = 'func.php must use Imagine THUMBNAIL_OUTBOUND for cover/crop generation.';
}

if (strpos($func_source, 'function fn_resize_image(') !== false) {
    $errors[] = 'func.php must not redefine fn_resize_image; addon-safe hook processing is required.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_generate_thumbnail_post') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_generate_thumbnail_post.';
}

if (strpos($func_source, "Storage::instance('images')->put(") === false) {
    $errors[] = 'func.php must overwrite the generated thumbnail via Storage::instance(\'images\')->put().';
}

if (strpos($func_source, "'overwrite' => true") === false) {
    $errors[] = 'func.php must write thumbnails with overwrite=true.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_install') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_install.';
}

if (strpos($func_source, 'function fn_is2or_thumbnail_crop_uninstall') === false) {
    $errors[] = 'func.php must define fn_is2or_thumbnail_crop_uninstall.';
}

if (strpos($func_source, 'ExifMetadataReader') === false) {
    $errors[] = 'func.php must use ExifMetadataReader so outbound thumbnails keep core orientation behavior.';
}

if (strpos($func_source, 'if (isset($metadata[\'exif.Orientation\']))') === false
    && strpos($func_source, "if (isset(\n                \$metadata['exif.Orientation']))") === false) {
    $errors[] = 'func.php must inspect EXIF orientation metadata before generating outbound thumbnails.';
}

if (strpos($func_source, "'jpeg_quality'") === false || strpos($func_source, "'png_compression_level'") === false) {
    $errors[] = 'func.php must preserve core thumbnail encoder options.';
}

if ($controller_source === false) {
    $errors[] = 'Backend controller for thumbnail cache clearing must exist.';
} else {
    if (strpos($controller_source, 'if (empty($auth[\'user_type\']) || $auth[\'user_type\'] !== \'A\')') === false) {
        $errors[] = 'Backend controller must deny non-admin users.';
    }

    if (strpos($controller_source, "if (\$mode === 'clear')") === false) {
        $errors[] = 'Backend controller must expose clear mode explicitly.';
    }

    if (strpos($controller_source, "if (\$mode === 'repair_settings')") === false) {
        $errors[] = 'Backend controller must expose repair_settings mode explicitly.';
    }

    if (strpos($controller_source, 'fn_is2or_thumbnail_crop_clear_thumbnails()') === false) {
        $errors[] = 'Backend controller must call fn_is2or_thumbnail_crop_clear_thumbnails().';
    }

    if (strpos($controller_source, 'fn_is2or_thumbnail_crop_repair_settings()') === false) {
        $errors[] = 'Backend controller must call fn_is2or_thumbnail_crop_repair_settings().';
    }

    if (strpos($controller_source, 'is2or_thumbnail_crop.text_settings_repaired') === false || strpos($controller_source, 'is2or_thumbnail_crop.text_settings_repair_failed') === false) {
        $errors[] = 'Backend controller must use repair-settings success/failure notifications.';
    }

    if (strpos($controller_source, 'return [CONTROLLER_STATUS_NO_PAGE];') === false) {
        $errors[] = 'Backend controller must reject unknown modes with CONTROLLER_STATUS_NO_PAGE.';
    }


    if (strpos($controller_source, "return [CONTROLLER_STATUS_REDIRECT, 'addons.update?addon=is2or_thumbnail_crop']") === false) {
        $errors[] = 'Backend controller must redirect back to the addon page.';
    }
}

if ($template_source === false) {
    $errors[] = 'Backend manage-tools hook template must exist.';
} else {
    if (strpos($template_source, 'is2or_thumbnail_crop.repair_settings') === false) {
        $errors[] = 'Backend manage-tools hook template must point to dispatch=is2or_thumbnail_crop.repair_settings.';
    }

    if (strpos($template_source, 'is2or_thumbnail_crop.clear') === false) {
        $errors[] = 'Backend manage-tools hook template must point to dispatch=is2or_thumbnail_crop.clear.';
    }

    if (strpos($template_source, 'is2or_thumbnail_crop.repair_settings') === false) {
        $errors[] = 'Backend manage-tools hook template must use the repair-settings language key.';
    }

    if (strpos($template_source, 'is2or_thumbnail_crop.clear_thumbnails') === false) {
        $errors[] = 'Backend manage-tools hook template must use the clear-thumbnails language key.';
    }
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Thumbnail crop addon scaffold contract OK.\n";
