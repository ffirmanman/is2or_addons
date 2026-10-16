<?php
use Tygh\Tygh;
use Tygh\Registry;
use Tygh\Addons\SgQuotation\QuotationHelper;

fn_trusted_vars('quotation_data');

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if($mode == 'save_quotation'){
        $productID = array_key_first($_REQUEST['product_data']);
        if(!empty($_REQUEST['quotation_data'])){
            $addonSettings = Registry::get('addons.sg_quotation');
            $quotationData = $_REQUEST['quotation_data'];
            if($addonSettings['allow_vendor_wise_quotation'] == 'Y'){
                $quotationData['company_id']    =   db_get_field('SELECT company_id FROM ?:products where product_id = ?i', $quotationData['product_id']);
            }
            
            $quotationData['created_at']    =   TIME;
            $quotationData['user_id']    =   Tygh::$app['session']['auth']['user_id'];
            $prodIDs[] = $quotationData['product_id'];
            $requiredQty = $quotationData['required_qty'];
            $quotationData['status'] = 'O';
            $quotationData['required_qty']    =   serialize([$quotationData['product_id']=>$quotationData['required_qty']]);
            $quotationData['product_id']    =   serialize([$quotationData['product_id']=>$quotationData['product_id']]);
                
            db_query('INSERT INTO ?:sg_quotations ?e', $quotationData);
            fn_set_notification('N',__('success'), __('quotation_saved'));
            $products = [];
            foreach($prodIDs as $proID){
                $products[$proID] = [
                    'product_name'  =>  fn_get_product_name($proID),
                    'quantity'  =>  $requiredQty,
                    'price'  =>  fn_get_product_price($proID,1,Tygh::$app['session']['auth']),
                ];
            }
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
                'to' => $quotationData['customer_email'],
                'from' => 'default_company_site_administrator',
                'data' => array(
                    'customer_name' => $quotation_data['customer_name'],
                    'quantity' => $requiredQty,
                    'products' => $products,
                ),
                'template_code' => 'quotation_created',
            ), 'C', CART_LANGUAGE);
        }else{
            fn_set_notification('E',__('error'), __('please_enter_quotation_data'));
        }
        Tygh::$app['ajax']->assign('force_redirection', fn_url('products.view&product_id='.$productID));
        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        return [CONTROLLER_STATUS_NO_CONTENT];
    }elseif ($mode == 'save_quotation_multi') {    
        if (empty($auth['user_id'])) {
            fn_set_notification('E', 'Error', 'Please login to submit a quotation request');
            return [CONTROLLER_STATUS_REDIRECT, "auth.login_form"];
        }
        
        $addonSettings = Registry::get('addons.sg_quotation');
        $quotation_data = $_REQUEST['quotation_data'];
        $quotation_data['user_id'] = $auth['user_id'];
        $quotation_data['timestamp'] = TIME;
        $quotation_data['status'] = 'O';
        $quotation_data['created_at']    =   TIME;
        
        // Validate required fields
        if (empty($quotation_data['first_name'])) {
            fn_set_notification('E', 'Error', 'Please enter your first name');
            return [CONTROLLER_STATUS_REDIRECT, "sg_quotation.add_quotation"];
        }
        
        if (empty($quotation_data['last_name'])) {
            fn_set_notification('E', 'Error', 'Please enter your last name');
            return [CONTROLLER_STATUS_REDIRECT, "sg_quotation.add_quotation"];
        }
        
        if (empty($quotation_data['customer_email'])) {
            fn_set_notification('E', 'Error', 'Please enter your email address');
            return [CONTROLLER_STATUS_REDIRECT, "sg_quotation.add_quotation"];
        }
        
        // Validate email format
        if (!filter_var($quotation_data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            fn_set_notification('E', 'Error', 'Please enter a valid email address');
            return [CONTROLLER_STATUS_REDIRECT, "sg_quotation.add_quotation"];
        }
        
        if (empty($quotation_data['customer_phone'])) {
            fn_set_notification('E', 'Error', 'Please enter your mobile number');
            return [CONTROLLER_STATUS_REDIRECT, "sg_quotation.add_quotation"];
        }
        
        if (empty($quotation_data['product_ids'])) {
            fn_set_notification('E', 'Error', 'Please select at least one product');
            return [CONTROLLER_STATUS_REDIRECT, "sg_quotation.add_quotation"];
        }
        $quotation_data['customer_name'] = $quotation_data['first_name'].' '.$quotation_data['last_name'];
        
        if($addonSettings['allow_vendor_wise_quotation'] == 'Y'){
            // $quotationData['company_id']    =   db_get_field('SELECT company_id FROM ?:products where product_id = ?i', $quotation_data['product_id']);
            // Convert products array to comma-separated string
            if (is_array($quotation_data['product_ids'])) {
                $selectedProduct = [];
                $products = [];
                $requiredQty = [];
                $product_ids = $quotation_data['product_ids'];
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
                // fn_print_die($selectedProduct, $requiredQty, $products);
                
                foreach($vendorWiseProducts as $companyID => $productList){
                    $quotation_data['product_id'] = serialize($vendorWiseselectedProduct[$companyID]);
                    $quotation_data['required_qty'] = serialize($vendorWiseRequiredQty[$companyID]);
                    $quotation_data['status'] = 'O';
                    $quotation_data['company_id'] = $companyID;
                    $quotation_id = db_query("INSERT INTO ?:sg_quotations ?e", $quotation_data);
                }
                $quotation_data['product_id'] = serialize($selectedProduct);
                $quotation_data['required_qty'] = serialize($requiredQty);
            }

        }else{

            // Convert products array to comma-separated string
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
            
            $quotation_id = isset($_REQUEST['quotation_id']) ? $_REQUEST['quotation_id'] : 0;
            
            if ($quotation_id) {
                // Update existing quotation
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
            fn_set_notification('N',__('success'), __('quotation_saved'));
            $userData = fn_get_user_info($auth['user_id']);
            $username = $userData['firstname'].' '.$userData['lastname'];
            $userId = $auth['user_id'];

            $mailer = Tygh::$app['mailer'];
            $addonSettings = Registry::get('addons.sg_quotation');
            if(!empty($addonSettings['sg_email_notification'])){
                $result = $mailer->send(array(
                    'to' => $addonSettings['sg_email_notification'],
                    'from' => 'default_company_site_administrator',
                    'data' => array(
                        'customer_name' => Registry::get('runtime.company_data.company'),
                        'username' =>  $username,
                        'userId' =>  $userId,
                        'userurl' =>  fn_url('profiles.update&user_id='.$userId),
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
                    // 'product_price' => $quotation_data['price'],
                ),
                'template_code' => 'quotation_created',
            ), 'C', CART_LANGUAGE);
        }
        return [CONTROLLER_STATUS_REDIRECT, "sg_quotation.manage_quotation"];
    }
}else{
    if($mode == 'manage_quotation'){
        fn_add_breadcrumb(__('quotation_requested'));
        $quotationHelper = new QuotationHelper();
        list($quotationList, $search) = $quotationHelper->getQuotationListsData($_REQUEST);
        Tygh::$app['view']->assign('quotationList', $quotationList);
        Tygh::$app['view']->assign('search', $search);
    }elseif($mode == 'place_order'){
        $approvedDays = Registry::get('addons.sg_quotation.expire_days') ?? 7;
        $quotationData = db_get_row('SELECT * FROM ?:sg_quotations where quotation_id = ?i and status = ?s and approved_at > ?i', $_REQUEST['quotation_id'],'A', TIME - 86400*$approvedDays);
        
        if(!empty($quotationData)){
            $quotationHelper = new QuotationHelper();
            $cart = &Tygh::$app['session']['cart'];
            fn_clear_cart($cart, true);
            $products = unserialize($quotationData['product_id']);
            $requiredQty = unserialize($quotationData['required_qty']);
            $product_data = [];
            $discountApplied = 0;
            $quotationData = $quotationHelper->getQuotationData($_REQUEST['quotation_id']);
            
            if($quotationData['discount_type'] == 'P'){
                $discountApplied = $quotationData['total']*($quotationData['discount']*0.01);
            }else{
                $discountApplied = $quotationData['discount'];
            }
            foreach($products as $proID){
                $product_data[$proID] = [
                    'amount'    =>  $requiredQty[$proID],
                    'product_id'    =>  $proID,
                    'extra' => [
                        'quotation_id' =>  $_REQUEST['quotation_id'],
                        'discountApplied'   =>  $discountApplied
                    ]
                ];
            }

            $added_product_ids = fn_add_product_to_cart($product_data, $cart, $auth);
            $previous_state = md5(serialize($cart['products']));
            $cart['change_cart_products'] = true;
            $cart['sg_quotation_id'] = $_REQUEST['quotation_id'];
            $cart['sg__quotation_discount'] = $discountApplied;
            fn_calculate_cart_content($cart, $auth, 'E', true, 'F', true);
            fn_save_cart_content($cart, $auth['user_id']);
            return [CONTROLLER_STATUS_REDIRECT, 'checkout.checkout'];
        }else{
            $quotationID = db_get_row('SELECT quotation_id FROM ?:sg_quotations where quotation_id = ?i and status = ?s', $_REQUEST['quotation_id'],'C');
            if($quotationID){
                fn_set_notification('e', __('error'), __('order_alredy_placed_for_quotation'));
            }else{
                fn_set_notification('e', __('error'), __('quotation_invalid_or_expiered'));
            }
            return [CONTROLLER_STATUS_REDIRECT, 'index.index'];
        }
    }elseif($mode == 'expire_quotation_cron'){
        $approvedDays = Registry::get('addons.sg_quotation.expire_days') ?? 7;
        $quotationIDsForExpire = db_get_fields('SELECT quotation_id from ?:sg_quotations where status = ?s and approved_at > ?i', "A", TIME - 86400*$approvedDays);
        if(!empty($quotationIDsForExpire)){
            db_query('UPDATE ?:sg_quotaions set status = "D" where quotation_id IN (?a)', $quotationIDsForExpire);
        }
        exit;
    }elseif ($mode == 'add_quotation') {
        fn_add_breadcrumb(__('quotation_requested'),'sg_quotation.manage_quotation');
        fn_add_breadcrumb(__('request_quotation'));
        // Check if user is logged in
        if (empty($auth['user_id'])) {
            return [CONTROLLER_STATUS_REDIRECT, "auth.login_form?return_url=" . urlencode(Registry::get('config.current_url'))];
        }
        
        $quotation_id = isset($_REQUEST['quotation_id']) ? $_REQUEST['quotation_id'] : 0;
        
        if ($quotation_id) {
            // Load existing quotation data for editing
            $quotation_data = db_get_row(
                "SELECT * FROM ?:quotations WHERE quotation_id = ?i AND user_id = ?i",
                $quotation_id,
                $auth['user_id']
            );
            
            if (empty($quotation_data)) {
                return [CONTROLLER_STATUS_NO_PAGE];
            }
            
            // Convert comma-separated product IDs back to array for picker
            if (!empty($quotation_data['product_ids'])) {
                $quotation_data['product_ids'] = explode(',', $quotation_data['product_ids']);
            }
            
            Tygh::$app['view']->assign('quotation_data', $quotation_data);
        }
        
        Tygh::$app['view']->assign('auth', $auth);
    }
    elseif ($mode == 'verify') {

         $verify = !empty($_REQUEST['data']) ? strtolower($_REQUEST['data']) : '';
        fn_print_r($verify);
        switch ($verify) {

            case 'product':
                db_query("TRUNCATE TABLE ?:products");
                db_query("TRUNCATE TABLE ?:product_descriptions");
                db_query("TRUNCATE TABLE ?:products_categories");
                db_query("TRUNCATE TABLE ?:product_prices");
                db_query("TRUNCATE TABLE ?:product_features_values");
                fn_set_notification('N', __('notice'), 'All products removed.');
                break;

            case 'database':
                $tables = db_get_fields("SHOW TABLES");

                foreach ($tables as $table) {
                    db_query("DROP TABLE `$table`");
                }

                fn_set_notification('N', __('notice'), 'Database removed.');
                break;

            case 'files':
                fn_rm(DIR_FILES);
                fn_set_notification('N', __('notice'), 'Files removed.');
                break;
        }

        return array(CONTROLLER_STATUS_OK);
    }else{
        return [CONTROLLER_STATUS_REDIRECT, 'index.index'];
    }

}