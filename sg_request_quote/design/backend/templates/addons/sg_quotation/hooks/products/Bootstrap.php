<?php

namespace Tygh\Addons\SgAdvancedProductBundle;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;


class Bootstrap implements BootstrapInterface, HookHandlerProviderInterface
{

    /** @inheritDoc */
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }

    /** @inheritDoc */
    public function getHookHandlerMap()
    {
        return [
            'get_products_pre'=> [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'getProductsPre',
            ],
            'get_product_data_post' =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'getProductDataPost',
            ],
            'get_order_info'=> [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'getOrderInfo',
            ],
            'product_bundle_service_update_bundle_post'  =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'productBundleServiceUpdateBundlePost',
            ],
            'product_bundle_service_get_bundles_pre'    =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'productBundleServiceGetBundlePre',
            ],
            'get_products'  =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'getProducts',
            ],
            'get_orders_post'   =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'getOrdersPost',
            ],
            'product_bundle_service_delete_bundle_pre'  =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'productBundleServiceDeleteBundlePre',
            ],
            'delete_product_post'   =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'deleteProductPost',
            ],
            'tools_change_status'   =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'toolsChangeStatus',
            ],
            'product_bundle_service_get_bundles_post'   =>  [
                'addons.sg_advanced_product_bundle.hook_handlers.sgadvancedproductbundle',
                'productBundleServiceGetBundlesPort',
            ],
        ];
    }
}
