<?php

namespace Tygh\Addons\SgQuotation\HookHandlers;
use Tygh\Registry;
use Tygh\Tygh;
use Tygh\Storage;
use Tygh\Addons\SgQuotation\DirectConnectHelper;

class QuotationHookHandler{
    public function orderPlacementRoutines($order_id, $force_notification, $order_info, $_error, $redirect_url, $allow_external_redirect){
        foreach($order_info['products'] as $product){
            if(!empty($product['extra']) && !empty($product['extra']['quotation_id'])){
                $quotationID = $product['extra']['quotation_id'];
                $updateData = [
                    'order_id'  =>  $order_id,
                    'order_placed_at'   =>  TIME,
                    'status'            =>  'C'
                ];
                db_query('UPDATE ?:sg_quotations set ?u where quotation_id = ?i', $updateData, $quotationID);
            }
        }
    }

    public function caculateCartPost(&$cart, &$auth, $calculate_shipping, $calculate_taxes, $options_style, $apply_cart_promotions, $cart_products, $product_groups){
        if (!empty($cart['sg_quotation_id']) && $cart['sg__quotation_discount'] > 0) {
            $discount_amount = $cart['sg__quotation_discount'];
            $cart['subtotal'] = (float)($cart['subtotal']-$discount_amount);
            $cart['discount'] = (float)($discount_amount);
            $cart['total'] = (float)($cart['total']-$discount_amount);
            $cart['total'] = max(0, $cart['total']);
        }

    }
    
    public function getProducts($params, $fields, $sortings, &$condition, $join, $sorting, $group_by, $lang_code, $having){
        if(!empty($_REQUEST['sg_quotation_product_picker'])){
            $condition .= db_quote(
                ' AND (products.allow_sg_quotation = "Y")'
            );
        }
    }

    public function getProductDataPost($product_data, $auth, $preview, $lang_code){
        // fn_print_r($product_data, $auth, $preview, $lang_code);
    }
}
