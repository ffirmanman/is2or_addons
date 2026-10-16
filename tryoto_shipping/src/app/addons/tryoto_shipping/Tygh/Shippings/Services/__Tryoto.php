<?php

namespace Tygh\Shippings\Services;

use Tygh\Shippings\IService;
use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

class Tryoto implements IService
{
    protected $_shipping_info = [];
    protected $_service_params = [];
    protected $_addon = [];
    protected $_access_token = '';

    /**
     * Constructor called by CS-Cart
     *
     * @param array $shipping_info  shipping info passed by CS-Cart
     * @param array $service_params shipping service params (not used - addon settings used)
     */
    public function __construct($shipping_info = [], $service_params = [])
    {
        $this->_shipping_info = $shipping_info;
        $this->_service_params = $service_params;

        // Load addon/global settings (you must create these in addon settings)
        $this->_addon = (array) Registry::get('addons.tryoto');
    }

    /**
     * CS-Cart entry: return array of shipping rates (or false on error)
     *
     * @return array|false
     */
    public function getSimpleRates()
    {
        $request = $this->getRequestData();

        // Authenticate and obtain access token
        $this->authenticate();

        if (empty($this->_access_token)) {
            // no token -> can't call API
            return false;
        }

        $headers = [
            'Authorization: Bearer ' . $this->_access_token,
            'Content-Type: application/json; charset=utf-8',
        ];

        try {
            $raw = Http::post($request['url'], json_encode($request['data']), [
                'headers' => $headers
            ]);
        } catch (\Exception $e) {
            // network / http error
            return false;
        }

        $response = json_decode($raw, true);

        // Convert API response into CS-Cart rates
        return $response;
    }

    /**
     * Build request payload for TryOTO
     *
     * Keeps serviceType & deliveryType but still expects multiple deliveryCompany results
     *
     * @return array ['url' => string, 'data' => array]
     */
    public function getRequestData()
    {

        $this->_delivery_option = $this->_shipping_info['service_code'];
        $this->_pickup_type = $this->_shipping_info['service_params']['pickup_type'];

        // Prepare package data
        $package_info = $this->_shipping_info['package_info'];
        $origination = $this->_shipping_info['package_info']['origination'];
        $location = $this->_shipping_info['package_info']['location'];

        // Prepare shipping address
        $shipping_address = array(
            'address' => $location['address'] ?? '',
            'country' => $location['country'] ?? '',
            'state' => $location['state'] ?? '',
            'city' => $location['city'] ?? '',
            'zipcode' => $location['zipcode'] ?? '',

        );

        // Prepare origin address
        $origin_address = array(
            'address' => $origination['address'] ?? '',
            'country' => $origination['country'] ?? '',
            'state' => $origination['state'] ?? '',
            'city' => $origination['city'] ?? '',
            'zipcode' => $origination['zipcode'] ?? '',
        );


        // Calculate total weight
        $weight = 0.1; // Default minimum
        if (!empty($package_info['W'])) {
            $weight_data = fn_expand_weight($package_info['W']);
            $shipment_weight = ($weight_data['plain'] * Registry::get('settings.General.weight_symbol_grams')) / 1000;

            // Gunakan (float) dan round untuk memastikan tipe data Numeric
            $weight = (float) round($shipment_weight > 0 ? $shipment_weight : 0.1, 2);
        }

        // Calculate package dimensions
        $length = 5; $width = 5; $height = 5; // Default values
        if (!empty($package_info['packages']) && is_array($package_info['packages'])) {
            foreach ($package_info['packages'] as $package) {
                $p_l = !empty($package['shipping_params']['box_length']) ? $package['shipping_params']['box_length'] : 5;
                $p_w = !empty($package['shipping_params']['box_width']) ? $package['shipping_params']['box_width'] : 5;
                $p_h = !empty($package['shipping_params']['box_height']) ? $package['shipping_params']['box_height'] : 5;

                $length = max($length, $p_l);
                $width  = max($width, $p_w);
                $height = max($height, $p_h);
            }
        }

        // Pastikan Numeric dengan 2 desimal maksimal
        $length = (float) round($length, 2);
        $width  = (float) round($width, 2);
        $height = (float) round($height, 2);

        $api_url = ($this->_shipping_info['service_params']['test_mode'] == 'Y') ? TRYOTO_API_TEST_URL : TRYOTO_API_URL;

        // Final Request Data
        $request_data = [
            'url' => $api_url . '/rest/v2/checkOTODeliveryFee',
            'data' => [
                'serviceType'     => (string) $this->_delivery_option,
                'deliveryType'    => $this->_shipping_info['service_params']['pickup_type'],
                'originCity'      => $origin_address['city'],
                'destinationCity' => $shipping_address['city'],
                'weight'          => $weight,
                'length'          => $length,
                'width'           => $width,
                'height'          => $height
            ]
        ];

        return $request_data;
    }

    /**
     * Authenticate to TryOTO and set $_access_token
     */
    protected function authenticate()
    {
        // token endpoint & credentials must be in addon settings
        $token_url  = $this->_addon['token_url'] ?? ($this->_addon['api_token_url'] ?? '');
        $client_id  = $this->_addon['client_id'] ?? '';
        $secret_key = $this->_addon['secret_key'] ?? '';

        if (empty($token_url) || empty($client_id) || empty($secret_key)) {
            $this->_access_token = '';
            return;
        }

        try {
            $raw = Http::post($token_url, json_encode([
                'client_id'  => $client_id,
                'secret_key' => $secret_key,
            ]), [
                'headers' => ['Content-Type' => 'application/json']
            ]);
        } catch (\Exception $e) {
            $this->_access_token = '';
            return;
        }

        $resp = json_decode($raw, true);

        if (!empty($resp['access_token'])) {
            $this->_access_token = $resp['access_token'];
            return;
        }

        // Some token endpoints may return token in different key
        if (!empty($resp['token'])) {
            $this->_access_token = $resp['token'];
            return;
        }

        $this->_access_token = '';
    }

    /**
     * Convert TryOTO API response to CS-Cart format (array of rate arrays)
     *
     * Must return either false (error) or an array like:
     * [
     *   ['service_code'=>..., 'service_name'=>..., 'cost'=>..., 'delivery_time'=>..., 'currency'=>..., 'extra'=>...],
     *   ...
     * ]
     *
     * @param array $response
     * @return array|false
     */
    public function processResponse($response)
    {
        if (empty($response) || empty($response['success']) || empty($response['deliveryCompany']) || !is_array($response['deliveryCompany'])) {
            return false;
        }

        $rates = [];

        foreach ($response['deliveryCompany'] as $item) {
            $company_name = $item['deliveryCompanyName'] ?? ($item['deliveryCompany'] ?? 'Tryoto');
            $option_name  = $item['deliveryOptionName'] ?? ($item['deliveryOption'] ?? 'Shipping Service');
            $option_id    = $item['deliveryOptionId'] ?? ($item['delivery_option_id'] ?? uniqid());
            $price        = isset($item['price']) ? (float) $item['price'] : (isset($item['cost']) ? (float)$item['cost'] : 0.0);
            $currency     = $item['currency'] ?? ($response['currency'] ?? ($this->_addon['currency'] ?? 'SAR'));
            $sla_raw      = $item['avgDeliveryTime'] ?? ($item['avg_delivery_time'] ?? '');

            $rates[] = [
                'service_code'  => $option_id,
                'service_name'  => $company_name . ' - ' . $option_name,
                'cost'          => $price, // CS-Cart expects 'cost' key
                'delivery_time' => $this->formatDeliveryTime($sla_raw),
                'currency'      => $currency,
                'extra'         => $item
            ];
        }

         // Return one "main rate" but attach all rates inside
        return [
            'cost'  => $rates[0]['cost'],      // CS-Cart requires a single cost
            'error' => false,
            'delivery_time' => $rates[0]['delivery_time'],
            'rates' => $rates                  // <-- YOU USE THIS IN CHECKOUT
        ];
    }

    /**
     * Format SLA text like "1to2WorkingDays" -> "1 to 2 Working Days"
     *
     * @param string $text
     * @return string
     */
    protected function formatDeliveryTime($text)
    {
        if (empty($text)) {
            return '';
        }

        // convert 1to2 -> 1 to 2
        $text = preg_replace('/(\d)to(\d)/i', '$1 to $2 ', $text);

        // insert spaces between lowerUpper camel-case e.g. WorkingDays -> Working Days
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);

        // normalize repeated spaces
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /* --- IService required methods (minimal implementations) --- */

    public function allowMultithreading()
    {
        return false;
    }

    public function prepareData($shipping_info)
    {
        $this->_shipping_info = $shipping_info;
    }

    public function processErrors($response)
    {
        return [];
    }
}
