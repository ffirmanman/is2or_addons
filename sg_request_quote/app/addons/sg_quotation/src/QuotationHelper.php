<?php

namespace Tygh\Addons\SgQuotation;
use Tygh\Registry;
use Tygh\Tygh;
use Tygh\Storage;

class QuotationHelper{
    public function getQuotationListsData($params){
        $auth = Tygh::$app['session']['auth'];
        $conditions = ' WHERE 1';
        if(AREA == 'A'){
            $itemsPerPage = !empty($_REQUEST['items_per_page']) ? $_REQUEST['items_per_page'] : Registry::get('settings.Appearance.admin_elements_per_page');
        }else{
            $itemsPerPage = !empty($_REQUEST['items_per_page']) ? $_REQUEST['items_per_page'] : Registry::get('settings.Appearance.elements_per_page');
            $conditions .= ' AND user_id = '.$auth['user_id'];
        }
        if(!empty($params['customer'])){
            $conditions .= ' AND customer_name LIKE "%'.$params['customer'].'%"';
        }
        if(!empty($params['email'])){
            $conditions .= ' AND customer_email LIKE "%'.$params['email'].'%"';
        }
        if(!empty($params['order_id'])){
            $conditions .= ' AND order_id = "'.$params['order_id'].'"';
        }
        if(!empty($params['status'])){
            $conditions .= ' AND status = "'.$params['status'].'"';
        }
        if(!empty($params['time_from'])){
            $conditions .= ' AND created_at > '.fn_parse_date($params['time_from']);
        }
        if(!empty($params['time_to'])){
            $conditions .= ' AND created_at < '.(fn_parse_date($params['time_to']) + 86400);
        }
        
        if(Registry::get('runtime.company_id')){
            $conditions .= ' AND company_id = '.Registry::get('runtime.company_id');
        }

        $page = $params['page'] ?? 1;
        $limit = ' LIMIT 0,'.$itemsPerPage;
        if($page > 1){
            $offset = ($page - 1)*$itemsPerPage;
            $limit = ' LIMIT '.$offset.', '.$itemsPerPage;
        }
        $orderBy = ' ORDER BY quotation_id DESC';
        
        $quotationList = db_get_array('SELECT * FROM ?:sg_quotations ?p ?p ?p',$conditions, $orderBy, $limit);
        
        if(!empty($quotationList)){
            foreach($quotationList as &$quote){
                $product_data = [];
                if(!empty($quote['product_id']) && !empty($quote['required_qty'])){
                    $total = 0;
                    $totalQuantity = 0;
                    $productIds = unserialize($quote['product_id']);
                    $requiredQty = unserialize($quote['required_qty']);
                    foreach($productIds as $proID){
                        $product_data[$proID] = [
                            'product_name'    =>  fn_get_product_name($proID),
                            'required_qty'    =>  $requiredQty[$proID] ?? 'NAN',
                            'price'    =>  fn_get_product_price($proID, 1, $auth) ,
                        ];
                        $total += fn_get_product_price($proID, 1, $auth) * (int)$requiredQty[$proID];
                        $totalQuantity += (int)$requiredQty[$proID] ?? 0;
                    }
                    $quote['total'] = $total;
                    $quote['totalQuantity'] = $totalQuantity;
                }
                if(!empty($quote['status']) && $quote['status'] == 'A'){
                    $quote['placeorderhref'] = fn_url('sg_quotation.place_order&quotation_id='.$quote['quotation_id'], 'C');
                }
                $quote['product_data'] = $product_data;
            }
        }
        $totalItems = db_get_field('SELECT count(*) FROM ?:sg_quotations ?p',$conditions);
        $search = [];
        $search = [
            'page'  =>  $page,
            'items_per_page'  =>  $itemsPerPage,
            'total_items'   =>  $totalItems ?? 0,
        ];
        $search = array_merge($search, $params);
        return [$quotationList, $search];
    }

    public function getQuotationData($quotationID){
        $auth = Tygh::$app['session']['auth'];
        $quotationData = db_get_row('SELECT * FROM ?:sg_quotations where quotation_id = ?i', $quotationID);
        if(!empty($quotationData)){
            $product_data = [];
            if(!empty($quotationData['product_id']) && !empty($quotationData['required_qty'])){
                $productIds = unserialize($quotationData['product_id']);
                $requiredQty = unserialize($quotationData['required_qty']);
                $total = 0;
                $totalQuantity = 0;
                
                foreach($productIds as $proID){
                    $product_data[$proID] = [
                        'product_name'    =>    fn_get_product_name($proID),
                        'required_qty'    =>    $requiredQty[$proID] ?? 'NAN',
                        'price'           =>    fn_get_product_price($proID, 1, $auth),
                    ];
                    $total += fn_get_product_price($proID, 1, $auth) * $requiredQty[$proID];
                    $totalQuantity += $requiredQty[$proID] ?? 0;
                }
                $quotationData['total'] = $total;
                $quotationData['totalQuantity'] = $totalQuantity;
            }
            $quotationData['product_data'] = $product_data;
            if(!empty($quotationData['status']) && $quotationData['status'] == 'A'){
                $quotationData['placeorderhref'] = fn_url('sg_quotation.place_order&quotation_id='.$quotationData['quotation_id'], 'C');
            }
        }
        
        return $quotationData;
    }
}
