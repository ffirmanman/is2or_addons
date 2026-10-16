<?php

namespace Tygh\Addons\SgQuotation;

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
            'order_placement_routines'=> [
                'addons.sg_quotation.hook_handlers.quotation',
                'orderPlacementRoutines',
            ],
            'calculate_cart_post'=> [
                'addons.sg_quotation.hook_handlers.quotation',
                'caculateCartPost',
            ],
            'get_products'=> [
                'addons.sg_quotation.hook_handlers.quotation',
                'getProducts',
            ],
            'get_product_data_post' =>  [
                'addons.sg_quotation.hook_handlers.quotation',
                'getProductDataPost',
            ]
        ];
    }
}
