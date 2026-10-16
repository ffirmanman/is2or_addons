<?php

namespace IS2OR\SplIntegration;

use Tygh\Http;
use Tygh\Registry;

class Api
{
    private $api_key;
    private $base_url = 'https://apina.address.gov.sa';

    public function __construct()
    {
        $addon_settings = Registry::get('addons.is2or_spl_integration');
        $this->api_key = $addon_settings['api_key'];
    }

    private function getHeaders()
    {
        return [
            'Content-Type: application/json',
            'Cache-Control: no-cache',
            'api_key: ' . $this->api_key
        ];
    }

    private function makeRequest($path, $data = [])
    {
        $url = $this->base_url . $path;

        $result = false;
        $message = '';
        try {
            $response = Http::get($url, $data, [
                'headers' => $this->getHeaders()
            ]);

            list($result, $message) = $this->processResponse($response);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        return [$result, $message];
    }

    private function processResponse($response)
    {
        $result = false;
        $message = '';

        if ($response) {
            $data = json_decode($response, true);

            if (empty($data)) {
                // Try iconv for non-UTF8 responses
                $utf8 = @iconv("Windows-1256", "UTF-8//IGNORE", $response);
                $data = json_decode($utf8, true);
            }

            if (!empty($data['Addresses'])) {
                $result = $data['Addresses'];
            } else {
                $message = $data['statusdescription'] ?? 'Could not generate address';
            }
        }

        return [$result, $message];
    }

    /**
     * Generate full address from short address using new API endpoint
     * Maps response fields for customer and vendor use
     *
     * @param string $shortAddress
     * @param string $language 'E' for English, 'A' for Arabic
     * @return array [$result, $message]
     */
    public function generateShortAddress($shortAddress, $language = 'E')
    {
        $data = [
            'format'       => 'json',
            'language'     => $language,
            'page'         => 1,
            'encode'       => 'UTF8',
            'shortaddress' => $shortAddress,
            'api_key'      => $this->api_key,
        ];

        $path = '/NationalAddress/FullNAByShortAddress/FullNAByShortAddress';
        list($result, $message) = $this->makeRequest($path, $data);

        if ($result) {
            // Map response fields to our standard format
            $result = array_map(function ($address) {
                return $this->mapAddressFields($address);
            }, $result);
        }

        return [$result, $message];
    }

    /**
     * Verify a building/zip/additional combo against SPL National Address.
     *
     * @param string $buildingNo    4-digit building number
     * @param string $zipCode       4-digit zip code
     * @param string $additionalNo  4-digit additional number
     * @return array [found => bool, message => string]
     */
    public function verifyAddress($buildingNo, $zipCode, $additionalNo)
    {
        $data = [
            'format'          => 'json',
            'language'        => 'E',
            'encode'          => 'UTF8',
            'Buildingnumber'  => $buildingNo,
            'Zipcode'         => $zipCode,
            'Additionalnumber'=> $additionalNo,
            'api_key'         => $this->api_key,
        ];

        $path = '/NationalAddress/v3.1/address/address-verify';

        try {
            $response = Http::get($this->base_url . $path, $data, [
                'headers' => $this->getHeaders(),
            ]);

            if (!$response) {
                return ['found' => false, 'message' => 'Empty response from SPL', 'error' => true];
            }

            $json = json_decode($response, true);
            if (empty($json) || !isset($json['success'])) {
                $utf8 = @iconv('Windows-1256', 'UTF-8//IGNORE', $response);
                $json = json_decode($utf8, true);
            }

            if (empty($json)) {
                return ['found' => false, 'message' => 'Invalid response from SPL', 'error' => true];
            }

            if (!empty($json['addressfound'])) {
                return ['found' => true, 'message' => 'Address verified'];
            }

            return [
                'found'   => false,
                'message' => $json['statusdescription'] ?? 'Address not found in National Address registry',
            ];
        } catch (\Exception $e) {
            return ['found' => false, 'message' => 'SPL API error: ' . $e->getMessage(), 'error' => true];
        }
    }

    /**
     * Search for addresses using free text.
     *
     * @param string $addressString Free text address
     * @param string $language      'E' for English, 'A' for Arabic
     * @return array Mapped address results, empty array if none found
     */
    public function freeTextSearch($addressString, $language = 'E')
    {
        $data = [
            'format'       => 'json',
            'language'     => $language,
            'page'         => 1,
            'encode'       => 'UTF8',
            'addressstring'=> $addressString,
            'api_key'      => $this->api_key,
        ];

        $path = '/NationalAddress/v3.1/address/address-free-text';
        list($result, $message) = $this->makeRequest($path, $data);

        if ($result) {
            $result = array_map(function ($address) {
                return $this->mapAddressFields($address);
            }, $result);
        }

        return $result ?: [];
    }

    /**
     * Map SPL API response fields to our standard format
     */
    private function mapAddressFields($address)
    {
        return [
            // Raw SPL fields
            'ShortAddress'      => $address['ShortAddress'] ?? '',
            'Latitude'          => $address['Latitude'] ?? '',
            'Longitude'         => $address['Longitude'] ?? '',
            'BuildingNumber'    => $address['BuildingNumber'] ?? '',
            'PostCode'          => $address['PostCode'] ?? '',
            'AdditionalNumber'  => $address['AdditionalNumber'] ?? '',

            // English fields
            'Street_L2'         => $address['Street_L2'] ?? '',
            'District_L2'       => $address['District_L2'] ?? '',
            'City_L2'           => $address['City_L2'] ?? '',
            'RegionName_L2'     => $address['RegionName_L2'] ?? '',
            'city_id'           => $address['city_id'] ?? 0,

            // Arabic fields
            'Street'            => $address['Street'] ?? '',
            'District'          => $address['District'] ?? '',
            'City'              => $address['City'] ?? '',
            'RegionName'        => $address['RegionName'] ?? '',

            // Combined address lines
            'Address1'          => $address['Address1'] ?? '',
            'Address2'          => $address['Address2'] ?? '',
            'FullAddress'       => trim(($address['Address1'] ?? '') . ' ' . ($address['Address2'] ?? '')),

            // TryOTO Customer mapping
            'tryoto_customer' => [
                'lat'                   => $address['Latitude'] ?? '',
                'lon'                   => $address['Longitude'] ?? '',
                'address'               => trim(($address['Address1'] ?? '') . ' ' . ($address['Address2'] ?? '')),
                'district'              => $address['District_L2'] ?? $address['District'] ?? '',
                'city'                  => $address['City_L2'] ?? $address['City'] ?? '',
                'buildingNo'            => $address['BuildingNumber'] ?? '',
                'street'                => $address['Street_L2'] ?? $address['Street'] ?? '',
                'postcode'              => $address['PostCode'] ?? '',
                'shortAddressCode'      => $address['ShortAddress'] ?? '',
                'secondaryAddressNumber'=> $address['AdditionalNumber'] ?? '',
            ],

            // TryOTO Vendor/Sender mapping
            'tryoto_sender' => [
                'lat'                        => $address['Latitude'] ?? '',
                'lon'                        => $address['Longitude'] ?? '',
                'senderAddressLine'          => trim(($address['Address1'] ?? '') . ' ' . ($address['Address2'] ?? '')),
                'senderDistrict'             => $address['District_L2'] ?? $address['District'] ?? '',
                'senderCity'                 => $address['City_L2'] ?? $address['City'] ?? '',
                'senderBuildingNo'           => $address['BuildingNumber'] ?? '',
                'senderStreet'               => $address['Street_L2'] ?? $address['Street'] ?? '',
                'senderPostcode'             => $address['PostCode'] ?? '',
                'senderShortAddressCode'     => $address['ShortAddress'] ?? '',
                'senderSecondaryAddressNumber' => $address['AdditionalNumber'] ?? '',
            ],
        ];
    }
}
