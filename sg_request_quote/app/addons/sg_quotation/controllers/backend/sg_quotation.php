<?php

use Tygh\Tygh;
use Tygh\Registry;
use Tygh\Addons\SgQuotation\QuotationHelper;

fn_trusted_vars('quotation_data');

if($mode == 'manage_quotation'){
    $quotationHelper = new QuotationHelper();
    list($quotationList, $search) = $quotationHelper->getQuotationListsData($_REQUEST);
    Tygh::$app['view']->assign('quotationList', $quotationList);
    Tygh::$app['view']->assign('search', $search);
    
}elseif($mode == 'update_status'){

    if(!empty($_REQUEST['id']) && !empty($_REQUEST['status'])){
        $user_id = Tygh::$app['session']['auth']['user_id'] ?? 0;
        $result = db_query('UPDATE ?:sg_quotations set status = ?s, approved_by = ?i where quotation_id = ?i', $_REQUEST['status'],$user_id, $_REQUEST['id']);
        if ($result) {
            $quotationHelper = new QuotationHelper();
            $quotationData = $quotationHelper->getQuotationData($_REQUEST['id']);
            
            $product_ids = unserialize($quotationData['product_ids']);
            $required_qty = unserialize($quotationData['required_qty']);
            foreach($product_ids as $productID){
                $selectedProduct[$productID] = $productID;
                $requiredQty[$productID] = $productID['amount'];
                $products[$productID] = [
                    'product_name'  =>  fn_get_product_name($productID),
                    'quantity'  =>  $productID['amount'],
                    'price'  =>  fn_get_product_price($productID,1,Tygh::$app['session']['auth']),
                ];
            }
            
            if($_REQUEST['status'] == 'D'){
                $mailer = Tygh::$app['mailer'];
                $result = $mailer->send(array(
                    'to' => $quotationData['customer_email'],
                    'from' => 'default_company_site_administrator',
                    'data' => array(
                        'customer_name' => $quotationData['customer_name'],
                        'products' => $quotationData['product_data'],
                        'total' => $quotationData['total'],
                        'totalQuantity' => $quotationData['totalQuantity'],
                        'reason'    =>  $_REQUEST['quotation']['reason'],
                    ),
                    'template_code' => 'quotation_declined',
                ), 'C', CART_LANGUAGE);
            }else{
                $quotationID = $product['extra']['quotation_id'];
                $user_id = Tygh::$app['session']['auth']['user_id'] ?? 0;
                db_query('UPDATE ?:sg_quotations set approved_at = ?s  where quotation_id = ?i', TIME, $_REQUEST['id']);
                $mailer = Tygh::$app['mailer'];
                $result = $mailer->send(array(
                    'to' => $quotationData['customer_email'],
                    'from' => 'default_company_site_administrator',
                    'data' => array(
                        'customer_name' => $quotationData['customer_name'],
                        'products' => $quotationData['product_data'],
                        'total' => $quotationData['total'],
                        'totalQuantity' => $quotationData['totalQuantity'],
                        'placeorderhref'    =>  fn_url('sg_quotation.place_order&quotation_id='.$_REQUEST['id'], 'C'),
                        'quotation_total'     =>  $quotationData['total'],
                        'quotation_discount'  =>  $quotationData['total'] - $quotationData['discounted_price']
                    ),
                    'template_code' => 'quotation_approved',
                ), 'C', CART_LANGUAGE);
            }
            
            fn_set_notification('N', __('notice'), __('status_changed'));
        } else {
            if (isset($params['show_error_notice']) && !$params['show_error_notice']) {
                return true;
            }
            fn_set_notification('E', __('error'), __('error_status_not_changed'));
            Tygh::$app['ajax']->assign('return_status', $old_status);
        }
    }
}elseif($mode == 'disapprove_quotation'){
    if(!empty($_REQUEST['quotation'])){
        $quotation = $_REQUEST['quotation'];
        $reason = $quotation['reason'] ?? '';
        // fn_print_die($quotation['reason'], empty(trim($quotation['reason'])));
        if(empty(trim($quotation['reason']))){
            fn_set_notification('E', __('error'), __('please_fill_reason_for_disapprove'));
            Tygh::$app['ajax']->assign('force_redirection', fn_url('sg_quotation.manage_quotation'));
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            exit;
        }
        $result = db_query('UPDATE ?:sg_quotations set status = ?s, disapproved_reason = ?s where quotation_id = ?i', $quotation['status'], $reason, $quotation['quotation_id']);
        $quotationData = db_get_row('SELECT * FROM ?:sg_quotations where quotation_id = ?i', $quotation['quotation_id']);
        $quotationHelper = new QuotationHelper();
        $quotationData = $quotationHelper->getQuotationData($quotation['quotation_id']);

        if (is_array($quotation_data['product_ids'])) {
            $selectedProduct = [];
            $products = [];
            $requiredQty = [];
            $product_ids = $quotation_data['product_ids'];
            foreach($product_ids as $productID){
                $selectedProduct[$productID['product_id']] = $productID['product_id'];
                $requiredQty[$productID['product_id']] = $productID['amount'];
                $products[$productID['product_id']] = [
                    'product_name'  =>  fn_get_product_name($productID['product_id']),
                    'quantity'  =>  $productID['amount'],
                    'price'  =>  fn_get_product_price($productID['product_id'],1,Tygh::$app['session']['auth']),
                ];
            }
            $quotation_data['product_id'] = serialize($selectedProduct);
            $quotation_data['required_qty'] = serialize($requiredQty);
        }
        // fn_print_die($_REQUEST, $quotationData);
        if($quotation['status'] == 'D'){
            
            $mailer = Tygh::$app['mailer'];
            $result = $mailer->send(array(
                'to' => $quotationData['customer_email'],
                'from' => 'default_company_site_administrator',
                'data' => array(
                    'customer_name' =>  $quotationData['customer_name'],
                    'quantity'  =>  $quotationData['required_qty'],
                    'product_name' => fn_get_product_name($quotationData['product_id']),
                    'product_price' => $quotationData['price'],
                    'products' => $quotationData['product_data'],
                    'decline_reason'    =>  $quotation['reason']
                ),
                'template_code' => 'quotation_declined',
            ), 'C', CART_LANGUAGE);
            fn_set_notification('N', __('notice'), __('status_changed'));
            Tygh::$app['ajax']->assign('force_redirection', fn_url('sg_quotation.manage_quotation'));
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        }
    }
}elseif($mode == 'approve_quotation'){
    if(!empty($_REQUEST['quotation_data'])){
        $quotation = $_REQUEST['quotation_data'];
        $user_id = Tygh::$app['session']['auth']['user_id'] ?? 0;
        $result = db_query('UPDATE ?:sg_quotations set status = ?s, approved_by = ?i,  approved_at = ?s  where quotation_id = ?i', 'A', $user_id, TIME, $quotation['quotation_id']);
        $quotationData = db_get_row('SELECT * FROM ?:sg_quotations where quotation_id = ?i', $quotation['quotation_id']);
        $quotationHelper = new QuotationHelper();
        $quotationData = $quotationHelper->getQuotationData($quotation['quotation_id']);
        $mailer = Tygh::$app['mailer'];
        
        $result = $mailer->send(array(
            'to' => $quotationData['customer_email'],
            'from' => 'default_company_site_administrator',
            'data' => array(
                'customer_name' => $quotationData['customer_name'],
                'quantity'      => $quotationData['required_qty'],
                'product_name'  => fn_get_product_name($quotationData['product_id']),
                'product_price' => $quotationData['price'],
                'products'      => $quotationData['product_data'],
                'placeorderhref'    =>  fn_url('sg_quotation.place_order&quotation_id='.$quotation['quotation_id'], 'C'),
                'quotation_total'     =>  $quotationData['total'],
                'quotation_discount'  =>  $quotationData['total'] - $quotationData['discounted_price']
            ),
            'template_code' => 'quotation_approved',
        ), 'C', CART_LANGUAGE);
        fn_set_notification('N', __('notice'), __('status_changed'));
        Tygh::$app['ajax']->assign('force_redirection', fn_url('sg_quotation.manage_quotation'));
        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        exit;
    }
}elseif($mode == 'disaprove'){
    Tygh::$app['view']->assign('quotationdata', $_REQUEST);
}elseif($mode == 'quotation_detail'){
    if(!empty($_REQUEST['id'])){
        $quotationHelper = new QuotationHelper();
        $quotationData = $quotationHelper->getQuotationData($_REQUEST['id']);
        Tygh::$app['view']->assign('quotationData', $quotationData);
    }
}elseif($mode == 'save_quotation'){
    if(!empty($_REQUEST['quotation_data'])){
        $quotationData = $_REQUEST['quotation_data'];
        $addonSettings = Registry::get('addons.sg_quotation');
        if (empty($quotationData['user_id'])) {
            fn_set_notification('E', 'Error', 'Please select user for quotation');
            return [CONTROLLER_STATUS_REDIRECT, "sg_quotation.create_quotation"];
        }
        $userInfo = fn_get_user_info($quotationData['user_id']);
        $quotation_data = $_REQUEST['quotation_data'];
        $quotation_data['user_id'] = $quotationData['user_id'];
        $quotation_data['timestamp'] = TIME;
        $quotation_data['status'] = 'O';
        $quotation_data['customer_email']=   $userInfo['email'] ?? '';
        $quotation_data['customer_phone']=   $userInfo['phone'] ?? '';
        $quotation_data['customer_name'] =   ($userInfo['firstname'] ?? '') . ' ' . ($userInfo['lastname'] ?? '');
        $quotation_data['created_at']    =   TIME;

        if($addonSettings['allow_vendor_wise_quotation'] == 'Y'){
            if (!empty($quotation_data['products']) && is_array($quotation_data['products'])) {
                $selectedProduct = [];
                $products = [];
                $requiredQty = [];
                $product_ids = $quotation_data['products'];
                foreach($product_ids as $productID){
                    $companyID = db_get_field('SELECT company_id FROM ?:products where product_id = ?i', $productID['product_id']);
                    $selectedProduct[$productID['product_id']] = $productID['product_id'];
                    $requiredQty[$productID['product_id']] = $productID['amount'];
                    $vendorWiseselectedProduct[$companyID][$productID['product_id']] = $productID['product_id'];
                    $vendorWiseRequiredQty[$companyID][$productID['product_id']] = $productID['amount'];
                    $products[$productID['product_id']] = [
                        'product_name'  =>  fn_get_product_name($productID['product_id']),
                        'quantity'  =>  $productID['amount'],
                        'price'  =>  fn_get_product_price($productID['product_id'],1,Tygh::$app['session']['auth']),
                    ];
                    $vendorWiseProducts[$companyID][$productID['product_id']] = [
                        'product_name'  =>  fn_get_product_name($productID['product_id']),
                        'quantity'  =>  $productID['amount'],
                        'price'  =>  fn_get_product_price($productID['product_id'],1,Tygh::$app['session']['auth']),
                    ];
                }
                foreach($vendorWiseProducts as $companyID => $productList){
                    $quotation_data['product_id'] = serialize($vendorWiseselectedProduct[$companyID]);
                    $quotation_data['required_qty'] = serialize($vendorWiseRequiredQty[$companyID]);
                    $quotation_data['status'] = 'O';
                    $quotation_data['company_id'] = $companyID;
                    $quotation_id = db_query("INSERT INTO ?:sg_quotations ?e", $quotation_data);
                }
                $quotation_data['product_id'] = serialize($selectedProduct);
                $quotation_data['required_qty'] = serialize($requiredQty);
            }else{
                fn_set_notification('E',__('error'), __('please_select_product_first'));
                return [CONTROLLER_STATUS_REDIRECT, 'sg_quotation.create_quotation'];
            }
        }else{

            if (!empty($quotation_data['products']) && is_array($quotation_data['products'])) {
                $selectedProduct = [];
                $products = [];
                $requiredQty = [];
                $product_ids = $quotation_data['products'];
                foreach($product_ids as $productID){
                    $selectedProduct[$productID['product_id']] = $productID['product_id'];
                    $requiredQty[$productID['product_id']] = $productID['amount'];
                    $products[$productID['product_id']] = [
                        'product_name'  =>  fn_get_product_name($productID['product_id']),
                        'quantity'  =>  $productID['amount'],
                        'price'  =>  fn_get_product_price($productID['product_id'],1,Tygh::$app['session']['auth']),
                    ];
                }
                $quotation_data['product_id'] = serialize($selectedProduct);
                $quotation_data['required_qty'] = serialize($requiredQty);
            }else{
                fn_set_notification('E',__('error'), __('please_select_product_first'));
                return [CONTROLLER_STATUS_REDIRECT, 'sg_quotation.create_quotation'];
            }
            $quotation_id = isset($_REQUEST['quotation_id']) ? $_REQUEST['quotation_id'] : 0;
        
            if ($quotation_id) {
                db_query(
                    "UPDATE ?:sg_quotations SET ?u WHERE quotation_id = ?i AND user_id = ?i",
                    $quotation_data,
                    $quotation_id,
                    $auth['user_id']
                );
                fn_set_notification('N', 'Success', 'Your quotation has been updated successfully');
            } else {
                // Create new quotation
                $quotation_data['status'] = 'O';
                $quotation_id = db_query("INSERT INTO ?:sg_quotations ?e", $quotation_data);
            }
        }
       
        fn_set_notification('N',__('success'), __('quotation_saved'));
        $mailer = Tygh::$app['mailer'];
        
        if(!empty($addonSettings['sg_email_notification'])){
            $result = $mailer->send(array(
                'to' => $addonSettings['sg_email_notification'],
                'from' => 'default_company_site_administrator',
                'data' => array(
                    'customer_name' => Registry::get('runtime.company_data.company'),
                    'quantity' => $quotation_data['required_qty'],
                    'products' => $products,
                ),
                'template_code' => 'quotation_created_admin',
            ), 'A', CART_LANGUAGE);
        }
        $result = $mailer->send(array(
            'to' => $quotation_data['customer_email'],
            'from' => 'default_company_site_administrator',
            'data' => array(
                'customer_name' => $quotation_data['customer_name'],
                'quantity' => $quotation_data['required_qty'],
                'products' => $products,
            ),
            'template_code' => 'quotation_created',
        ), 'C', CART_LANGUAGE);
    }else{
        fn_set_notification('E',__('error'), __('all_rquired_data_not_filled'));
    }
    return [CONTROLLER_STATUS_REDIRECT, 'sg_quotation.manage_quotation'];
}elseif($mode == 'delete'){
    $quoatationID = $_REQUEST['quotation_id'];
    if($quoatationID){
        db_query('DELETE FROM ?:sg_quotations where quotation_id = ?i', $quoatationID);
        fn_set_notification('N', __('success'), __('quotation_deleted'));
    }
    return [CONTROLLER_STATUS_REDIRECT, 'sg_quotation.manage_quotation'];
}elseif($mode == 'm_activate'){
    if(!empty($_REQUEST['product_ids'])){
        db_query('UPDATE ?:products set allow_sg_quotation = "Y" where product_id IN (?a)',$_REQUEST['product_ids']);
        fn_set_notification('N', __('success'), __('quotation_status_change_to_active'));
    }
}elseif($mode == 'm_disable'){
    if(!empty($_REQUEST['product_ids'])){
        db_query('UPDATE ?:products set allow_sg_quotation = "N" where product_id IN (?a)',$_REQUEST['product_ids']);
        fn_set_notification('N', __('success'), __('quotation_status_change_to_disable'));
    }
}elseif($mode == 'quotation_discount_popup'){
    if(!empty($_REQUEST['id'])){
        $quotationHelper = new QuotationHelper();
        $quotationData = $quotationHelper->getQuotationData($_REQUEST['id']);
        Tygh::$app['view']->assign('quotationData', $quotationData);
    }
}elseif($mode == 'apply_discount'){
    $quotationHelper = new QuotationHelper();
    if(!empty($_REQUEST['quotation_data']) && !empty($_REQUEST['quotation_data']['quotation_id'])){
        $quotation_id = $_REQUEST['quotation_data']['quotation_id'];
        $quotationDiscountArray = $_REQUEST['quotation_data'];
        $discountApplied = 0;
        if($quotationDiscountArray['discount_type'] == 'P'){
            $discountApplied = $quotationDiscountArray['total']*($quotationDiscountArray['discount']*0.01);
        }else{
            $discountApplied = $quotationDiscountArray['discount'];
        }
        $quotationData = $quotationHelper->getQuotationData($quotation_id);
        
        if($quotationData['total'] <= $discountApplied){
            fn_set_notification('E', __('discount_too_high'), __('discount_should_be_less_than_total'));
        }else{
            $updateQuotationData = [
                'discount'  =>  $quotationDiscountArray['discount'],
                'discount_type'  =>  $quotationDiscountArray['discount_type'],
                'discounted_price'  =>  $discountApplied,
            ];
            db_query('UPDATE ?:sg_quotations set ?u where quotation_id = ?i',$updateQuotationData, $quotation_id);
            fn_set_notification('N', __('notice'), __('discount_applied'));
            
        }

    }
    Tygh::$app['ajax']->assign('force_redirection', fn_url('sg_quotation.manage_quotation'));
    Tygh::$app['ajax']->assign('non_ajax_notifications', true);
}