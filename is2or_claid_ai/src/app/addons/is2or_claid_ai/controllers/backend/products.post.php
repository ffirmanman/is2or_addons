<?php

use Tygh\Registry;
use Tygh\Tygh;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'update') {
    $product_data = Tygh::$app['view']->getTemplateVars('product_data');

    if (!empty($product_data['product_id'])) {
        if ($product_data['product_id'] == fn_is2or_claid_ai_get_credits_product_id()) {
            $tabs = Registry::get('navigation.tabs');

            foreach ($tabs as $id => &$tab) {
                $tab['hidden'] = $id !== 'detailed';
            }
            unset($tab);
            Registry::set('navigation.tabs', $tabs);

            Tygh::$app['view']->assign('force_zero_company_id', true);
        } else {
            $company_id = Registry::get('runtime.company_id');
            if ($company_id) {
                Tygh::$app['view']->assign('is2or_claid_credits', fn_is2or_claid_ai_get_company_credits($company_id));
            }
        
            list($is2or_claid_images) = fn_is2or_claid_ai_get_images($company_id, [], 4);
            Tygh::$app['view']->assign('is2or_claid_images', $is2or_claid_images);
        
            Registry::set('navigation.tabs.is2or_claid_ai', [
                'title' => __('is2or_claid_ai.image_enhancer'),
                'js' => true
            ]);

            // Try-On section
            $tryon_enabled = false;
            $category_ids = $product_data['category_ids'];

            foreach ($category_ids as $category_id) {
                if (fn_is2or_claid_ai_is_tryon_category($category_id)) {

                    $tryon_enabled = true;
                    break;
                }
            }

            $garments = fn_is2or_claid_ai_get_garments($product_data['product_id']);
            Tygh::$app['view']->assign('is2or_claid_garments', $garments);

            if ($tryon_enabled) {
                Registry::set('navigation.tabs.is2or_claid_tryon', [
                    'title' => __('is2or_claid_ai.tryon'),
                    'js' => true
                ]);
            }
        }
    }
}