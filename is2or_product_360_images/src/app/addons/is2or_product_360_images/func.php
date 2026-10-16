<?php

use Tygh\Storage;

function fn_is2or_product_360_images_get_images($product_id) {
    $images =  db_get_hash_array(
        "SELECT * FROM ?:is2or_360_images WHERE object_type = ?s AND object_id = ?i ORDER BY position", 'image_id', 'product', $product_id
    );

    foreach ($images as &$image) {
        $image['image_path'] = 'images/360_images/' . $product_id . '/' . $image['prefix'] . '-' . $image['position'] . '.' . $image['extension'];
    }

    return $images;
}

function fn_is2or_product_360_images_get_image($image_id) {
    return db_get_row("SELECT * FROM ?:is2or_360_images WHERE image_id = ?i", $image_id);
}

function fn_is2or_product_360_images_update_image($image_id, $data) {
    if ($image_id) {
        db_query("UPDATE ?:is2or_360_images SET ?u WHERE image_id = ?i", $data, $image_id);
    } else {
        $image_id = db_query("INSERT INTO ?:is2or_360_images ?e", $data);
    }

    return $image_id;
}

function fn_is2or_product_360_images_delete_image($image_id) {
    db_query("DELETE FROM ?:is2or_360_images WHERE image_id = ?i", $image_id);
}

/* HOOKS FUNCTIONS */
function fn_is2or_product_360_images_update_product_post($product_data, $product_id, $lang_code, $create) {
    if ($product_id) {
        if (!empty($_REQUEST['deleted_360_images'])) {
            foreach ($_REQUEST['deleted_360_images'] as $deleted_image_id) {
                fn_is2or_product_360_images_delete_image($deleted_image_id);
            }
        }

        if (!empty($_REQUEST['360_images'])) {
            $path = '360_images/' . $product_id . '/';
            $prefix = '';
            for ($i = 0; $i < 8; $i++) {
                $prefix .= chr(rand(97, 122));
            }

            $storage = Storage::instance('images');

            $_360_images = $_REQUEST['360_images'] ?? [];
            $_360_image_files = $_FILES['360_images'] ?? [];

            $fileIndex = 0;
            foreach ($_360_images['image_id'] as $i => $image_id) {
                $position = $i+1;
                if ($image_id) {
                    $imageData = fn_is2or_product_360_images_get_image($image_id);

                    $oldFileName = $imageData['prefix'] . '-' . $imageData['position'] . '.' . $imageData['extension'];
                    $newFileName = $prefix . '-' . $position . '.' . $imageData['extension'];

                    // Rename file
                    if ($storage->isExist($path . $oldFileName)) {
                        $storage->copy($path . $oldFileName, $path . $newFileName);
                        $storage->delete($path . $oldFileName);
                    }

                    $imageData['position'] = $position;
                    $imageData['prefix'] = $prefix;

                } else {
                    $fileName = $_360_image_files['name'][$fileIndex];
                    $tmpFile = $_360_image_files['tmp_name'][$fileIndex];

                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = $prefix . '-' . $position . '.' . $extension;

                    $storage->put($path . $newFileName, [
                        'file' => $tmpFile,
                        'keep_original' => true
                    ]);

                    $imageData = [
                        'object_type' => 'product',
                        'object_id' => $product_id,
                        'position' => $position,
                        'prefix' => $prefix,
                        'extension' => $extension
                    ];

                    $fileIndex++;
                }

                fn_is2or_product_360_images_update_image($image_id, $imageData);
            }
        }
    }
}

function fn_is2or_product_360_images_get_product_data_post(&$product_data, $auth, $preview, $lang_code) {
    if ($product_data) {
        $product_data['360_images'] = fn_is2or_product_360_images_get_images($product_data['product_id']);
    }
}

function fn_is2or_product_360_images_delete_product_post($product_id, $product_deleted) {
    if ($product_id && $product_deleted) {
        db_query("DELETE FROM ?:is2or_360_images WHERE object_type = ?s AND object_id = ?i", 'product', $product_id);

        // also delete files
        $path = '360_images/' . $product_id;
        Storage::instance('images')->deleteDir($path);
    }
}
/* HOOKS FUNCTIONS */