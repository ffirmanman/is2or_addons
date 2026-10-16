<?php

namespace Tygh\Addons\Is2orAppVersion;

use Tygh\Api;

class PublicApi extends Api
{
    /**
     *
     * @var array
     */
    protected $public_resources = [
        'is2or_app_version',
    ];

    /**
     *
     * @return bool
     */
    protected function authenticate()
    {
        $resource = $this->request->getResource();

        if (in_array($resource, $this->public_resources)) {
            $this->auth = \Tygh\Tygh::$app['session']['auth'] = fn_fill_auth([]);
            return true;
        }

        return parent::authenticate();
    }
}
