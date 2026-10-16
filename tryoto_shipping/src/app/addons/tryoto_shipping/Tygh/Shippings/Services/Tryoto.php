<?php

namespace Tygh\Shippings\Services;

use Tygh;
use Tygh\Registry;
use Tygh\Shippings\IService;
use Tygh\Http;

class Tryoto implements IService
{

    /**
     * Availability multithreading in this module
     *
     * @var array $_allow_multithreading
     */
    private $_allow_multithreading = false;
    private $_access_token = '';
    private $_expires_in = 0;
    private $_token_type = '';
    private static $exchange_rate;
    private $_delivery_option = 0;

    private $_pickup_type = 1;

    private $_delivery_options = array();
    public $_shipping_info = array();
    private $module = 'tryoto';


    public function __construct()
    {
        $this->_delivery_options = json_decode(TRYOTO_SERVICE_TYPES, true);
    }

    public function allowMultithreading()
    {
        return $this->_allow_multithreading;
    }

    /**
     * Sets data to internal class variable
     *
     * @param array $shipping_info
     */
    public function prepareData($shipping_info)
    {
        $this->_shipping_info = $shipping_info;
        //$this->_shipping_info['service_params']['tryoto_provider_id'] = '111111';

    }


    /**
     * Gets shipping cost and information about possible errors
     *
     * @param string $resonse Reponse from Shipping service server
     * @return array  Shipping cost and errors
     */
    /**
     * Memproses response dari service server dan mengembalikan hasil ke CS-Cart.
     *
     * @param array $response Response dari getSimpleRates
     * @return array Hasil akhir yang berisi biaya, error, atau daftar layanan
     */
    public function processResponse($response)
    {
        // Inisialisasi return dengan struktur standar CS-Cart
        $return = [
            'cost'          => false,
            'error'         => false,
            'delivery_time' => false,
            'rates'         => [], // Selalu sediakan array kosong untuk list kurir
        ];

        // 1. Cek Error dari API secara dini
        $errors = $this->processErrors($response);
        if ($errors) {
            $return['error'] = $errors;
            return $return; // Hentikan proses jika ada error
        }

        $return = $this->processRates($response);

        return $return;
    }

    private function processRates($response)
    {
        $rates = array(
            'cost' => false,
            'error' => false,
            'delivery_time' => false,
            'rates' => array() // Tambahkan inisialisasi array rates
        );
        if (isset($response['success']) && $response['success'] && is_array($response['deliveryCompany'])) {

            foreach ($response['deliveryCompany'] as $delivery_company) {
                if (

                    !empty($this->_shipping_info['service_params']['courier_code'])
                    && ($this->_shipping_info['service_params']['courier_code'] !== $delivery_company['deliveryCompanyName'] ||
                        $this->_shipping_info['service_code'] !== $delivery_company['serviceType'])
                ) {
                    //continue;
                }

                $shipping_rate = $delivery_company['price'];
                $shipping_rate_currency = $delivery_company['currency'];

                // Konversi harga ke Primary Currency Toko
                $cost = fn_format_price_by_currency($shipping_rate, $shipping_rate_currency, CART_PRIMARY_CURRENCY);
                $delivery_time = isset($delivery_company['avgDeliveryTime']) ? $this->normalizeEtd($delivery_company['avgDeliveryTime']) : '';
                $delivery_id = $delivery_company['deliveryOptionId'];
                // --- INI BAGIAN PENTING ---
                // CS-Cart butuh key unik (deliveryOptionId) agar bisa muncul di list
                $rates['rates'][$delivery_id] = [
                    'name'          => $delivery_company['deliveryOptionName'],
                    'service_name'  => $delivery_company['deliveryOptionName'],
                    'cost'          => $cost,
                    'delivery_time' => $delivery_time,
                    'service_code' => $delivery_id,
                    'extra' => [
                        'tryoto_provider_id'   => $delivery_id,
                        'tryoto_provider_name' => $delivery_company['deliveryOptionName'],
                        'service_code'         => $delivery_id // Simpan cadangan di sini
                    ],
                    'service_params' => [
                        'tryoto_provider_id'   => $delivery_id,
                        'tryoto_provider_name' => $delivery_company['deliveryOptionName']
                    ]
                ];

                // Logika untuk menentukan harga termurah (untuk 'cost' utama)
                if ($rates['cost'] === false || $cost < $rates['cost']) {
                    $rates['cost'] = $cost;
                    $rates['delivery_time'] = $delivery_time;
                    // Simpan info tambahan ke session untuk digunakan saat buat order
                }
                $shipping_id = $this->_shipping_info['shipping_id'];
                Tygh::$app['session']['cart']['tryoto_providers'][$shipping_id] = [
                    'tryoto_provider_id'   => $delivery_id,
                    'tryoto_provider_name' => $delivery_company['deliveryOptionName']
                ];
            }
        }
        return $rates;
    }

    /**
     * Gets error message from shipping service server
     *
     * @param string $resonse Reponse from Shipping service server
     * @return string Text of error or false if no errors
     */
    public function processErrors($response)
    {
        if (!empty($response) && !$response['success']) {
            $response['errors'] = $response['errorMsg'];
            return $response['errors'];
        }

        return null;
    }

    /**
     * Prepare request information
     *
     * @return null Always null (method not allowed)
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
        $weight = 1.0; // Default minimum
        if (!empty($package_info['W'])) {
            $weight_data = fn_expand_weight($package_info['W']);
            $shipment_weight = ($weight_data['plain'] * Registry::get('settings.General.weight_symbol_grams')) / 1000;

            // Gunakan (float) dan round untuk memastikan tipe data Numeric
            $weight = (float) round($shipment_weight > 1 ? $shipment_weight : 1.0, 2);
        }

        // Calculate package dimensions
        $length = 5.0; $width = 5.0; $height = 5.0; // Default values
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

        $api_url = TRYOTO_MODE === 'live' ? TRYOTO_API_URL : TRYOTO_API_TEST_URL;

        // Final Request Data
        return [
            'url' => $api_url . '/rest/v2/checkOTODeliveryFee',
            'data' => [
                'serviceType'     => (string) $this->_delivery_option,
                'deliveryType'    => $this->_shipping_info['service_params']['pickup_type'],
                'originCity'      => $origin_address['city'],
                'destinationCity' => $shipping_address['city'],
                'weight'          => $weight > 0 ? $weight : 1.0,
                'length'          => $length,
                'width'           => $width,
                'height'          => $height
            ]
        ];
    }

    /**
     * Process simple request to shipping service server
     *
     * @return string Server response
     */
    public function getSimpleRates()
    {
        $data = $this->getRequestData();

        // check if there is json file with md5 json data name
//        $cache_filename = md5(json_encode($data)) . '.' . md5(date('Y-m-d')) . '.json';
//        $cache_dir = Registry::get('config.dir.cache_static') . '/tryoto/';
//
//        if (file_exists($cache_dir . $cache_filename)) {
//            return json_decode(fn_get_contents($cache_dir . $cache_filename), true);
//        }


        $this->_accessToken();

        if ($this->_access_token != '') {
            $headers = [
                'Authorization: Bearer ' . $this->_access_token,
                'Content-Type: application/json; charset=utf-8',
            ];

            $response = Http::post($data['url'], json_encode($data['data']), array('headers' => $headers));
            $response = json_decode($response, true);
        } else {
            $response = array();
        }

        // save response to json file cache
//        if (!empty($response)) {
//            fn_put_contents($cache_dir . $cache_filename, json_encode($response));
//        }

        return $response;
    }


    public static function getInfo()
    {
        return array(
            'name' => __('carrier_tryoto'),
            'tracking_url' => TRYOTO_TRACKING_URL
        );
    }

    private function _accessToken()
    {
        $api_url = TRYOTO_MODE === 'live' ? TRYOTO_API_URL : TRYOTO_API_TEST_URL;

        $api_url = $api_url . '/rest/v2/refreshToken';

        $tryoto_refresh_token = TRYOTO_CLIENT_ID;

        // Prepare request data
        $request_data = [
            'refresh_token' => $tryoto_refresh_token
        ];

        // Set headers
        $headers = [
            'Content-Type: application/json',
        ];


        // Check existing token
        $is_valid_token = false;
        $cache_file_path = Registry::get('config.dir.cache_static') . '/tryoto/tryoto_token.json';

        if (file_exists($cache_file_path)) {
            $tryoto_token_string = fn_get_contents($cache_file_path);
            $arr_tryoto_token = json_decode($tryoto_token_string, true);
            if (is_array($arr_tryoto_token) && count($arr_tryoto_token) == 3) {
                if ($arr_tryoto_token['expires_in'] > strtotime("now")) {
                    $is_valid_token = true;
                    $this->_access_token = $arr_tryoto_token['access_token'];
                    $this->_expires_in = $arr_tryoto_token['expires_in'];
                    $this->_token_type = $arr_tryoto_token['token_type'];
                }
            }
        }

        if (!$is_valid_token) {
            // Make the API request
            $response = Http::post($api_url, json_encode($request_data), [
                'headers' => $headers,
            ]);

            $response_data = json_decode($response, true);


            $http_status = Http::getStatus();

            if ($http_status >= 400) {
                $this->_access_token = '';
                $this->_expires_in = 0;
                $this->_token_type = '';
            } else {
                $this->_access_token = $response_data['access_token'];
                $this->_expires_in = $response_data['expires_in'];
                $this->_token_type = $response_data['token_type'];
            }

            $tryoto_token_string = json_encode(
                array(
                    'access_token' => $this->_access_token,
                    'expires_in' => $this->_expires_in + strtotime('now'),
                    'token_type' => $this->_token_type
                )
            );
            fn_put_contents($cache_file_path, $tryoto_token_string);
        }
    }

    private function normalizeEtd($etd) {
        // 1) Convert "1to2" → "1 to 2"
        $etd = preg_replace('/(\d+)to(\d+)/i', '$1 to $2', $etd);

        // 2) Add space between numbers and letters
        $etd = preg_replace('/(?<=\d)(?=[A-Za-z])/', ' ', $etd);

        // 3) Add space before uppercase letters (WorkingDays → Working Days)
        $etd = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $etd);
        return $etd;
    }
}
