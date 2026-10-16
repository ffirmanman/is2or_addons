<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use IS2OR\SplIntegration\Api as SplClient;

class SplCodes extends AEntity
{
    public function index($id = '', $params = array())
    {
        $short_address = isset($params['code']) ? trim($params['code']) : '';

        if (empty($short_address)) {
            return array(
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => array('message' => 'Short Address code is required')
            );
        }

        $spl_client = new SplClient();

        list($address_data, $error_message) = $spl_client->generateShortAddress($short_address);

        if ($address_data) {
            return array(
                'status' => Response::STATUS_OK,
                'data' => [
                    'short_address' => $short_address,
                    'addresses'     => $address_data // Mengembalikan array 'Addresses' dari API SPL
                ]
            );
        }

        return array(
            'status' => Response::STATUS_NOT_FOUND,
            'data' => array('message' => $error_message ?: 'Invalid Short Address')
        );
    }

    public function create($params) { return array('status' => Response::STATUS_METHOD_NOT_ALLOWED); }
    public function update($id, $params) { return array('status' => Response::STATUS_METHOD_NOT_ALLOWED); }
    public function delete($id) { return array('status' => Response::STATUS_METHOD_NOT_ALLOWED); }

    public function privilegesCustomer()
    {
        return [
            'index' => true // Mengizinkan GET request untuk Customer
        ];
    }
}
