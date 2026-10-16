<?php

declare(strict_types=1);

define('BOOTSTRAP', true);

require dirname(__DIR__) . '/src/app/addons/is2or_thumbnail_crop/func.php';

$errors = [];

$centered_subject = fn_is2or_thumbnail_crop_test_render_cover_thumbnail(
    fn_is2or_thumbnail_crop_test_create_centered_subject_blob(300, 240, [115, 44, 69, 153], [170, 40, 40]),
    40,
    40,
    true
);

$centered_without_trim = fn_is2or_thumbnail_crop_test_render_cover_thumbnail(
    fn_is2or_thumbnail_crop_test_create_centered_subject_blob(300, 240, [115, 44, 69, 153], [170, 40, 40]),
    40,
    40,
    false
);

$fill_with_trim = fn_is2or_thumbnail_crop_test_measure_subject_fill($centered_subject, [170, 40, 40]);
$fill_without_trim = fn_is2or_thumbnail_crop_test_measure_subject_fill($centered_without_trim, [170, 40, 40]);

if ($fill_with_trim['width'] < 30 || $fill_with_trim['height'] < 39) {
    $errors[] = 'Trim-before-cover should scale a tiny centered subject so it nearly fills the 40x40 thumbnail.';
}

if ($fill_without_trim['width'] > 16 || $fill_without_trim['height'] > 28) {
    $errors[] = 'Whole-canvas outbound baseline should still leave the tiny centered subject visibly small.';
}

$tight_blob = fn_is2or_thumbnail_crop_test_create_centered_subject_blob(40, 40, [2, 2, 36, 36], [40, 120, 190]);
$tight_bounds = fn_is2or_thumbnail_crop_detect_visible_bounds_from_blob($tight_blob, '#ffffff');

if ($tight_bounds !== null) {
    $errors[] = 'Already-tight imagery should skip trimming instead of inventing a crop box.';
}

$low_contrast_blob = fn_is2or_thumbnail_crop_test_create_centered_subject_blob(200, 200, [70, 40, 60, 120], [247, 247, 247]);
$low_contrast_bounds = fn_is2or_thumbnail_crop_detect_visible_bounds_from_blob($low_contrast_blob, '#ffffff');

if ($low_contrast_bounds !== null) {
    $errors[] = 'Low-contrast white-on-white imagery should fall back conservatively without trimming.';
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Thumbnail crop trim behavior OK.\n";

function fn_is2or_thumbnail_crop_test_render_cover_thumbnail(string $blob, int $target_width, int $target_height, bool $trim_first): string
{
    $source = imagecreatefromstring($blob);

    if (!$source) {
        throw new RuntimeException('Failed to decode source blob.');
    }

    try {
        if ($trim_first) {
            $bounds = fn_is2or_thumbnail_crop_detect_visible_bounds_from_resource($source, '#ffffff');

            if ($bounds !== null) {
                $cropped = imagecrop($source, $bounds);

                if ($cropped !== false) {
                    imagedestroy($source);
                    $source = $cropped;
                }
            }
        }

        $source_width = imagesx($source);
        $source_height = imagesy($source);
        $scale = max($target_width / $source_width, $target_height / $source_height);
        $scaled_width = max(1, (int) round($source_width * $scale));
        $scaled_height = max(1, (int) round($source_height * $scale));

        $scaled = imagecreatetruecolor($scaled_width, $scaled_height);
        $background = imagecolorallocate($scaled, 255, 255, 255);
        imagefill($scaled, 0, 0, $background);
        imagecopyresampled($scaled, $source, 0, 0, 0, 0, $scaled_width, $scaled_height, $source_width, $source_height);

        $thumbnail = imagecreatetruecolor($target_width, $target_height);
        imagefill($thumbnail, 0, 0, $background);

        $src_x = max(0, (int) floor(($scaled_width - $target_width) / 2));
        $src_y = max(0, (int) floor(($scaled_height - $target_height) / 2));
        imagecopy($thumbnail, $scaled, 0, 0, $src_x, $src_y, $target_width, $target_height);

        ob_start();
        imagepng($thumbnail);
        $output = (string) ob_get_clean();

        imagedestroy($scaled);
        imagedestroy($thumbnail);

        return $output;
    } finally {
        imagedestroy($source);
    }
}

function fn_is2or_thumbnail_crop_test_create_centered_subject_blob(int $width, int $height, array $subject_box, array $rgb): string
{
    $image = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($image, 255, 255, 255);
    $subject = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

    imagefill($image, 0, 0, $background);
    imagefilledrectangle(
        $image,
        $subject_box[0],
        $subject_box[1],
        $subject_box[0] + $subject_box[2] - 1,
        $subject_box[1] + $subject_box[3] - 1,
        $subject
    );

    ob_start();
    imagepng($image);
    $blob = (string) ob_get_clean();
    imagedestroy($image);

    return $blob;
}

function fn_is2or_thumbnail_crop_test_measure_subject_fill(string $blob, array $rgb): array
{
    $image = imagecreatefromstring($blob);

    if (!$image) {
        throw new RuntimeException('Failed to decode measured thumbnail.');
    }

    try {
        $width = imagesx($image);
        $height = imagesy($image);
        $left = $width;
        $right = -1;
        $top = $height;
        $bottom = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixel = fn_is2or_thumbnail_crop_get_pixel_rgb($image, $x, $y);

                if (fn_is2or_thumbnail_crop_color_distance($pixel, [
                    'red' => $rgb[0],
                    'green' => $rgb[1],
                    'blue' => $rgb[2],
                ]) <= 20) {
                    $left = min($left, $x);
                    $right = max($right, $x);
                    $top = min($top, $y);
                    $bottom = max($bottom, $y);
                }
            }
        }

        if ($right < $left || $bottom < $top) {
            return ['width' => 0, 'height' => 0];
        }

        return [
            'width' => $right - $left + 1,
            'height' => $bottom - $top + 1,
        ];
    } finally {
        imagedestroy($image);
    }
}
