<?php

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Metadata\ExifMetadataReader;
use Imagine\Image\Point;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Storage;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

function fn_is2or_thumbnail_crop_get_object_type_from_path(string $image_path): ?string
{
    $parts = explode('/', trim($image_path, '/'));

    return !empty($parts[0]) ? $parts[0] : null;
}

function fn_is2or_thumbnail_crop_get_enabled_object_types(): array
{
    $fallback = ['product', 'detailed'];
    $value = Registry::get('addons.is2or_thumbnail_crop.outbound_object_types');

    if (is_array($value)) {
        $enabled_object_types = array_values(array_filter($value, 'is_string'));

        return $enabled_object_types ?: $fallback;
    }

    if (is_string($value) && $value !== '') {
        $enabled_object_types = array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));

        return $enabled_object_types ?: $fallback;
    }

    return $fallback;
}

function fn_is2or_thumbnail_crop_is_enabled_for_type(?string $object_type): bool
{
    if (!$object_type) {
        return false;
    }

    return in_array($object_type, fn_is2or_thumbnail_crop_get_enabled_object_types(), true);
}

function fn_is2or_thumbnail_crop_clear_thumbnails(): bool
{
    Storage::instance('images')->deleteByPattern('thumbnails/*');

    return true;
}

function fn_is2or_thumbnail_crop_repair_settings(): bool
{
    return fn_reinstall_addon_files('is2or_thumbnail_crop');
}

function fn_is2or_thumbnail_crop_generate_outbound_contents(string $src, int $width, int $height, string $bg_color = '#ffffff')
{
    if ($width <= 0 || $height <= 0 || !is_readable($src)) {
        return false;
    }

    $settings = Settings::instance()->getValues('Thumbnails');
    $imagine = Tygh::$app['image'];
    $format = $settings['convert_to'] ?? 'png';

    if ($format === 'original') {
        $original_file_type = fn_get_image_extension(fn_get_mime_content_type($src, true));
        $format = $original_file_type ?: 'png';
    }

    try {
        $imagine->setMetadataReader(new ExifMetadataReader());
        $exif_supported = true;
    } catch (\Imagine\Exception\NotSupportedException $exception) {
        $exif_supported = false;
    }

    try {
        $image = $imagine->open($src);

        if ($exif_supported) {
            $metadata = $image->metadata()->toArray();

            if (isset($metadata['exif.Orientation'])) {
                $exif_orientation = (int) $metadata['exif.Orientation'];
            } elseif (isset($metadata['ifd0.Orientation'])) {
                $exif_orientation = (int) $metadata['ifd0.Orientation'];
            } else {
                $exif_orientation = null;
            }

            $rotation_angles = [
                3 => 180,
                6 => 90,
                8 => 270,
            ];

            if ($exif_orientation !== null && isset($rotation_angles[$exif_orientation])) {
                $image->rotate($rotation_angles[$exif_orientation]);
            }
        }

        fn_catch_exception(function () use ($image) {
            $image->usePalette(new \Imagine\Image\Palette\RGB());
        });

        $image = fn_is2or_thumbnail_crop_crop_to_visible_bounds($image, $bg_color);

        $filter = ($imagine instanceof \Imagine\Gd\Imagine)
            ? ImageInterface::FILTER_UNDEFINED
            : ImageInterface::FILTER_LANCZOS;

        $thumbnail = $image->thumbnail(
            new Box($width, $height),
            ImageInterface::THUMBNAIL_OUTBOUND,
            $filter
        );

        return $thumbnail->get($format, [
            'jpeg_quality' => $settings['jpeg_quality'] ?? null,
            'png_compression_level' => 9,
            'filter' => $filter,
            'flatten' => true,
        ]);
    } catch (\Exception $exception) {
        return false;
    }
}

function fn_is2or_thumbnail_crop_crop_to_visible_bounds($image, string $bg_color)
{
    $bounds = fn_is2or_thumbnail_crop_get_visible_bounds($image, $bg_color);

    if ($bounds === null) {
        return $image;
    }

    return $image->crop(
        new Point($bounds['x'], $bounds['y']),
        new Box($bounds['width'], $bounds['height'])
    );
}

function fn_is2or_thumbnail_crop_get_visible_bounds($image, string $bg_color): ?array
{
    if (!method_exists($image, 'get')) {
        return null;
    }

    try {
        $contents = $image->get('png');
    } catch (\Exception $exception) {
        return null;
    }

    return fn_is2or_thumbnail_crop_detect_visible_bounds_from_blob($contents, $bg_color);
}

function fn_is2or_thumbnail_crop_detect_visible_bounds_from_blob(string $contents, string $bg_color): ?array
{
    if ($contents === '' || !function_exists('imagecreatefromstring')) {
        return null;
    }

    $resource = @imagecreatefromstring($contents);

    if (!$resource) {
        return null;
    }

    try {
        return fn_is2or_thumbnail_crop_detect_visible_bounds_from_resource($resource, $bg_color);
    } finally {
        imagedestroy($resource);
    }
}

function fn_is2or_thumbnail_crop_detect_visible_bounds_from_resource($resource, string $bg_color): ?array
{
    $width = imagesx($resource);
    $height = imagesy($resource);

    if ($width < 3 || $height < 3) {
        return null;
    }

    $background_rgb = fn_is2or_thumbnail_crop_sample_background_rgb(
        $resource,
        $width,
        $height,
        fn_is2or_thumbnail_crop_hex_to_rgb($bg_color)
    );

    $top = fn_is2or_thumbnail_crop_find_content_edge($resource, $width, $height, $background_rgb, 'top');
    $bottom = fn_is2or_thumbnail_crop_find_content_edge($resource, $width, $height, $background_rgb, 'bottom');
    $left = fn_is2or_thumbnail_crop_find_content_edge($resource, $width, $height, $background_rgb, 'left');
    $right = fn_is2or_thumbnail_crop_find_content_edge($resource, $width, $height, $background_rgb, 'right');

    if ($top === null || $bottom === null || $left === null || $right === null) {
        return null;
    }

    if ($left >= $right || $top >= $bottom) {
        return null;
    }

    $trimmed_width = $right - $left + 1;
    $trimmed_height = $bottom - $top + 1;

    if ($trimmed_width >= $width && $trimmed_height >= $height) {
        return null;
    }

    $horizontal_trim = $left + ($width - $right - 1);
    $vertical_trim = $top + ($height - $bottom - 1);

    if ($horizontal_trim < 4 && $vertical_trim < 4) {
        return null;
    }

    if (($trimmed_width / $width) >= 0.9 && ($trimmed_height / $height) >= 0.9) {
        return null;
    }

    $padding_x = min($left, $width - $right - 1, max(1, (int) floor($trimmed_width * 0.03)));
    $padding_y = min($top, $height - $bottom - 1, max(1, (int) floor($trimmed_height * 0.03)));

    $left = max(0, $left - $padding_x);
    $top = max(0, $top - $padding_y);
    $right = min($width - 1, $right + $padding_x);
    $bottom = min($height - 1, $bottom + $padding_y);

    $trimmed_width = $right - $left + 1;
    $trimmed_height = $bottom - $top + 1;

    if ($trimmed_width < 4 || $trimmed_height < 4) {
        return null;
    }

    return [
        'x' => $left,
        'y' => $top,
        'width' => $trimmed_width,
        'height' => $trimmed_height,
    ];
}

function fn_is2or_thumbnail_crop_find_content_edge($resource, int $width, int $height, array $background_rgb, string $edge): ?int
{
    if ($edge === 'top') {
        for ($y = 0; $y < $height; $y++) {
            if (fn_is2or_thumbnail_crop_row_has_content($resource, $width, $y, $background_rgb)) {
                return $y;
            }
        }

        return null;
    }

    if ($edge === 'bottom') {
        for ($y = $height - 1; $y >= 0; $y--) {
            if (fn_is2or_thumbnail_crop_row_has_content($resource, $width, $y, $background_rgb)) {
                return $y;
            }
        }

        return null;
    }

    if ($edge === 'left') {
        for ($x = 0; $x < $width; $x++) {
            if (fn_is2or_thumbnail_crop_column_has_content($resource, $height, $x, $background_rgb)) {
                return $x;
            }
        }

        return null;
    }

    if ($edge === 'right') {
        for ($x = $width - 1; $x >= 0; $x--) {
            if (fn_is2or_thumbnail_crop_column_has_content($resource, $height, $x, $background_rgb)) {
                return $x;
            }
        }
    }

    return null;
}

function fn_is2or_thumbnail_crop_row_has_content($resource, int $width, int $y, array $background_rgb): bool
{
    $step = fn_is2or_thumbnail_crop_get_scan_step($width);
    $matches = 0;
    $required_matches = max(2, (int) ceil(($width / $step) * 0.02));

    for ($x = 0; $x < $width; $x += $step) {
        if (!fn_is2or_thumbnail_crop_is_background_pixel($resource, $x, $y, $background_rgb)) {
            $matches++;

            if ($matches >= $required_matches) {
                return true;
            }
        }
    }

    return false;
}

function fn_is2or_thumbnail_crop_column_has_content($resource, int $height, int $x, array $background_rgb): bool
{
    $step = fn_is2or_thumbnail_crop_get_scan_step($height);
    $matches = 0;
    $required_matches = max(2, (int) ceil(($height / $step) * 0.02));

    for ($y = 0; $y < $height; $y += $step) {
        if (!fn_is2or_thumbnail_crop_is_background_pixel($resource, $x, $y, $background_rgb)) {
            $matches++;

            if ($matches >= $required_matches) {
                return true;
            }
        }
    }

    return false;
}

function fn_is2or_thumbnail_crop_sample_background_rgb($resource, int $width, int $height, array $fallback_rgb): array
{
    $sample_points = [
        [0, 0],
        [$width - 1, 0],
        [0, $height - 1],
        [$width - 1, $height - 1],
        [(int) floor(($width - 1) / 2), 0],
        [(int) floor(($width - 1) / 2), $height - 1],
        [0, (int) floor(($height - 1) / 2)],
        [$width - 1, (int) floor(($height - 1) / 2)],
    ];

    $samples = [];

    foreach ($sample_points as [$x, $y]) {
        $samples[] = fn_is2or_thumbnail_crop_get_pixel_rgb($resource, $x, $y);
    }

    $median = [
        'red' => fn_is2or_thumbnail_crop_median(array_column($samples, 'red')),
        'green' => fn_is2or_thumbnail_crop_median(array_column($samples, 'green')),
        'blue' => fn_is2or_thumbnail_crop_median(array_column($samples, 'blue')),
    ];

    foreach ($samples as $sample) {
        if (fn_is2or_thumbnail_crop_color_distance($sample, $median) > 24) {
            return $fallback_rgb;
        }
    }

    return $median;
}

function fn_is2or_thumbnail_crop_is_background_pixel($resource, int $x, int $y, array $background_rgb): bool
{
    $pixel = fn_is2or_thumbnail_crop_get_pixel_rgb($resource, $x, $y);

    if (($pixel['alpha'] ?? 0) >= 120) {
        return true;
    }

    return fn_is2or_thumbnail_crop_color_distance($pixel, $background_rgb) <= 18;
}

function fn_is2or_thumbnail_crop_get_pixel_rgb($resource, int $x, int $y): array
{
    $color = imagecolorsforindex($resource, imagecolorat($resource, $x, $y));

    return [
        'red' => (int) ($color['red'] ?? 0),
        'green' => (int) ($color['green'] ?? 0),
        'blue' => (int) ($color['blue'] ?? 0),
        'alpha' => (int) ($color['alpha'] ?? 0),
    ];
}

function fn_is2or_thumbnail_crop_color_distance(array $left, array $right): int
{
    return max(
        abs((int) $left['red'] - (int) $right['red']),
        abs((int) $left['green'] - (int) $right['green']),
        abs((int) $left['blue'] - (int) $right['blue'])
    );
}

function fn_is2or_thumbnail_crop_hex_to_rgb(string $color): array
{
    $color = ltrim(trim($color), '#');

    if (strlen($color) === 3) {
        $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
    }

    if (strlen($color) !== 6 || !ctype_xdigit($color)) {
        return ['red' => 255, 'green' => 255, 'blue' => 255];
    }

    return [
        'red' => hexdec(substr($color, 0, 2)),
        'green' => hexdec(substr($color, 2, 2)),
        'blue' => hexdec(substr($color, 4, 2)),
    ];
}

function fn_is2or_thumbnail_crop_median(array $values): int
{
    sort($values, SORT_NUMERIC);
    $count = count($values);

    if ($count === 0) {
        return 0;
    }

    return (int) $values[(int) floor(($count - 1) / 2)];
}

function fn_is2or_thumbnail_crop_get_scan_step(int $length): int
{
    return max(1, (int) floor($length / 600));
}

function fn_is2or_thumbnail_crop_generate_thumbnail_post($th_filename, $lazy, $image_path, $width, $height, $image): bool
{
    $object_type = fn_is2or_thumbnail_crop_get_object_type_from_path((string) $image_path);

    if (!fn_is2or_thumbnail_crop_is_enabled_for_type($object_type)) {
        return true;
    }

    $width = (int) $width;
    $height = (int) $height;
    $image_path = (string) $image_path;

    if ($width <= 0 || $height <= 0 || $image_path === '') {
        return true;
    }

    $source_absolute_path = Storage::instance('images')->getAbsolutePath($image_path);
    list(, , , $tmp_path) = fn_get_image_size($source_absolute_path);

    if (empty($tmp_path)) {
        return true;
    }

    $contents = fn_is2or_thumbnail_crop_generate_outbound_contents(
        $tmp_path,
        $width,
        $height,
        (string) Registry::get('settings.Thumbnails.thumbnail_background_color')
    );

    if (empty($contents)) {
        return true;
    }

    $target_relative_path = 'thumbnails/' . $width . '/' . $height . '/' . $image_path;

    Storage::instance('images')->delete($target_relative_path);
    Storage::instance('images')->put($target_relative_path, [
        'contents' => $contents,
        'caching' => true,
        'overwrite' => true,
    ]);

    return true;
}

function fn_is2or_thumbnail_crop_install(): bool
{
    return fn_is2or_thumbnail_crop_clear_thumbnails();
}

function fn_is2or_thumbnail_crop_uninstall(): bool
{
    return fn_is2or_thumbnail_crop_clear_thumbnails();
}
