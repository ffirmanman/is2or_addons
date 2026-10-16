<?php

use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_product_notes_get_cart_item_id_by_product_id($product_id, $cart) {
    $cart_item_id_index = array_search($product_id, array_column($cart['products'], 'product_id'));

    return $cart_item_id_index !== false ? array_keys($cart['products'])[$cart_item_id_index] : false;
}

function fn_product_notes_get_notes($item_id, $section = 'cart', $order_id = null) {
    $out = false;
    if ($section == 'cart') {
        $cart = Tygh::$app['session']['cart'];
        
        $item_id = fn_product_notes_get_cart_item_id_by_product_id($item_id, $cart);
        
        if ($item_id !== false && !empty($cart['products'][$item_id]['notes'])) {
            $out = $cart['products'][$item_id]['notes'];
        } 
    } else if ($section == 'order' && $order_id) {
        $out = db_get_field("SELECT notes FROM ?:order_details WHERE item_id=?i AND order_id=?i", $item_id, $order_id);
    }
    
    return $out;
}

function fn_product_notes_update_notes($user_id, $product_id, $notes) {
    $note_id = db_get_field("SELECT note_id FROM ?:product_notes WHERE user_id=?i AND product_id=?i", $user_id, $product_id);

    if ($note_id) {
        db_query("UPDATE ?:product_notes SET ?u WHERE note_id=?i", [
            'notes' => $notes
        ], $note_id);
    } else {
        $note_id = db_query("INSERT INTO ?:product_notes ?e", [
            'user_id' => $user_id,
            'product_id' => $product_id,
            'notes' => $notes
        ]);
    }

    return $note_id;
}

function fn_product_notes_is_allow_notes($product_id) {
    return db_get_field("SELECT allow_notes FROM ?:products WHERE product_id=?i", $product_id);
}

/* HOOKS FUNCTIONS */
function fn_is2or_product_notes_create_order_details($order_id, $cart, &$order_details, $extra) {
    $item_id = $order_details['item_id'];

    $notes = !empty($cart['products'][$item_id]['notes'])
        ? $cart['products'][$item_id]['notes']
        : '';

    $order_details['notes'] = $notes;
}
/* HOOKS FUNCTIONS */

