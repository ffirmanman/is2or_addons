<?php

namespace Tygh\Addons\SgQuotation;

use Tygh\Addons\InstallerInterface;
use Tygh\Core\ApplicationInterface;
use Tygh\Registry;

class Installer implements InstallerInterface
{

    /**
     * @inheritDoc
     */
    public static function factory(ApplicationInterface $app)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function onBeforeInstall()
    {
    }

    /**
     * @inheritDoc
     */
    public function onInstall()
    {

        $addon_name = fn_get_lang_var('sg_quotation');
        
    }

    /**
     * @inheritDoc
     */
    public function onUninstall()
    {
        
    }
}
