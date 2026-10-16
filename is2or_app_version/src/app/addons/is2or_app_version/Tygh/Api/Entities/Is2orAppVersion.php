<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;

class Is2orAppVersion extends AEntity
{
    /**
     * GET /api/is2or_app_version
     * Returns current mobile app version info for iOS and Android.
     */
    public function index($id = '', $params = array())
    {
        $json_path = DIR_ROOT . '/app/addons/is2or_app_version/app-version.json';

        if (!file_exists($json_path)) {
            return array(
                'status' => Response::STATUS_NOT_FOUND,
                'data'   => array('message' => 'app-version.json not found')
            );
        }

        $data = json_decode(file_get_contents($json_path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'status' => Response::STATUS_INTERNAL_SERVER_ERROR,
                'data'   => array('message' => 'Invalid app-version.json format')
            );
        }

        return array(
            'status' => Response::STATUS_OK,
            'data'   => $data
        );
    }

    /**
     * Endpoint is read-only, disable create/update/delete.
     */
    public function create($params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
            'data'   => array('message' => 'Method not allowed')
        );
    }

    public function update($id, $params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
            'data'   => array('message' => 'Method not allowed')
        );
    }

    public function delete($id)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
            'data'   => array('message' => 'Method not allowed')
        );
    }

    /**
     *
     * @param  string $method_name
     * @return bool
     */
    public function isAccessable($method_name)
    {
        if ($method_name === 'index') {
            return true;
        }

        return false;
    }

    public function privileges()
    {
        return array(
            'create' => false,
            'update' => false,
            'delete' => false,
            'index'  => true,
        );
    }

    public function privilegesCustomer()
    {
        return array(
            'index' => true,
        );
    }
}
