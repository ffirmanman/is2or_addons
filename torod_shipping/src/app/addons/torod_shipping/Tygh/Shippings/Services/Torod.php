<?php

namespace Tygh\Shippings\Services;

use Tygh;
use Tygh\Registry;
use Tygh\Shippings\IService;
use Tygh\Http;

class Torod implements IService
{
    private $_allow_multithreading = false;
    private $_access_token = '';
    private $_shipping_info = array();

    public function __construct()
    {
    }

    public function allowMultithreading()
    {
        return $this->_allow_multithreading;
    }

    public function prepareData($shipping_info)
    {
        $this->_shipping_info = $shipping_info;
    }

    public static function getInfo()
    {
        return array(
            'name' => __('carrier_torod'),
            'tracking_url' => TOROD_TRACKING_URL
        );
    }

    public function getRequestData()
    {
        $package_info = $this->_shipping_info['package_info'];
        $origination = $package_info['origination'];
        $location = $package_info['location'];

        $weight = 1.0;
        if (!empty($package_info['W'])) {
            $weight_data = fn_expand_weight($package_info['W']);
            $weight = ($weight_data['plain'] * Registry::get('settings.General.weight_symbol_grams')) / 1000;
            $weight = max($weight, 1.0);
        }

        $api_url = TOROD_MODE === 'production' ? TOROD_PRODUCTION_API_URL : TOROD_API_URL;
        $resolved_city_id = fn_torod_resolve_city_id_from_location($location);
        $warehouse_code = $origination['warehouse_code'] ?? '';

        if (empty($warehouse_code) && !empty($origination['company_id'])) {
            $company = fn_get_company_data((int) $origination['company_id']);
            $warehouse_code = $company['warehouse_code'] ?? '';
        }

        return array(
            'url' => $api_url . '/courier/partners/list',
            'data' => array(
                'warehouse' => $warehouse_code,
                'customer_city_id' => $resolved_city_id,
                'payment' => 'Prepaid',
                'weight' => $weight,
                'order_total' => $this->_shipping_info['package_info']['total'] ?? 0,
                'no_of_box' => 1,
                'type' => $this->_shipping_info['service_params']['service_type'] ?? 'normal',
                'filter_by' => 'cheapest',
            )
        );
    }

    public function getSimpleRates()
    {
        $data = $this->getRequestData();

        $this->_accessToken();

        if ($this->_access_token != '') {
            $headers = array(
                'Authorization: Bearer ' . $this->_access_token,
                'Accept: application/json',
            );

            $response = Http::post($data['url'], http_build_query($data['data']), array(
                'headers' => $headers,
                'timeout' => TOROD_API_TIMEOUT,
            ));
            $response = json_decode($response, true);
        } else {
            $response = array();
        }

        return $response;
    }

    public function processResponse($response)
    {
        $return = array(
            'cost' => false,
            'error' => false,
            'delivery_time' => false,
            'rates' => array(),
        );

        $errors = $this->processErrors($response);
        if ($errors) {
            $return['error'] = $errors;
            return $return;
        }

        if (empty($response['data']) || !is_array($response['data'])) {
            $return['error'] = 'No rates returned from Torod API';
            return $return;
        }

        $rates = array(
            'cost' => false,
            'error' => false,
            'delivery_time' => false,
            'rates' => array()
        );

        foreach ($response['data'] as $courier) {
            $courier_code = trim((string) ($this->_shipping_info['service_params']['courier_code'] ?? ''));
            $courier_id = (string) ($courier['id'] ?? '');
            $courier_name = trim((string) ($courier['name'] ?? ''));

            if (
                $courier_code !== ''
                && $courier_id !== $courier_code
                && strcasecmp($courier_name, $courier_code) !== 0
            ) {
                continue;
            }

            $shipping_rate = isset($courier['price']) ? $courier['price'] : ($courier['rate'] ?? 0);
            $shipping_rate_currency = $courier['currency'] ?? CART_PRIMARY_CURRENCY;
            $cost = fn_format_price_by_currency($shipping_rate, $shipping_rate_currency, CART_PRIMARY_CURRENCY);
            $raw_delivery_time = $courier['avgDeliveryTime'] ?? ($courier['approximate_delivery_time'] ?? '');
            $delivery_time = $raw_delivery_time !== '' ? $this->normalizeEtd($raw_delivery_time) : '';
            $courier_id = $courier['id'];

            $rates['rates'][$courier_id] = array(
                'name' => $courier['name'],
                'service_name' => $courier['name'],
                'cost' => $cost,
                'delivery_time' => $delivery_time,
                'service_code' => $courier_id,
                'extra' => array(
                    'torod_provider_id' => $courier_id,
                    'torod_provider_name' => $courier['name'],
                    'service_code' => $courier_id,
                ),
                'service_params' => array(
                    'torod_provider_id' => $courier_id,
                    'torod_provider_name' => $courier['name'],
                ),
            );

            if ($rates['cost'] === false || $cost < $rates['cost']) {
                $rates['cost'] = $cost;
                $rates['delivery_time'] = $delivery_time;
            }

            $shipping_id = $this->_shipping_info['shipping_id'];
            Tygh::$app['session']['cart']['torod_providers'][$shipping_id] = array(
                'torod_provider_id' => $courier_id,
                'torod_provider_name' => $courier['name'],
            );
        }

        return $rates;
    }

    public function processErrors($response)
    {
        if (empty($response['status']) && !empty($response['message'])) {
            return $response['message'];
        }

        if (!empty($response['error'])) {
            return $response['error'];
        }

        return null;
    }

    private function _accessToken()
    {
        $this->_access_token = fn_torod_get_access_token();
    }

    private function normalizeEtd($etd)
    {
        $etd = preg_replace('/(\d+)to(\d+)/i', '$1 to $2', $etd);
        $etd = preg_replace('/(?<=\d)(?=[A-Za-z])/', ' ', $etd);
        $etd = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $etd);
        return $etd;
    }
}
