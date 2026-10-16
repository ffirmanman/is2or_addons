<?php

namespace IS2OR\MoyasarPayment;

use Tygh\Http;
use Tygh\Registry;

class Api
{
    private $base_url = 'https://api.moyasar.com/v1';
    private $public_key;
    private $secret;

    public function __construct()
    {
        $addon_settings = Registry::get('addons.moyasar_payment');

        $this->public_key = $addon_settings['publicKey'];
        $this->secret = $addon_settings['secretKey'];
    }

    private function getHeaders() {
        return [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->secret . ':')
        ];
    }

    public function createPayoutAccount($iban) {
        $success = false;
        $message = '';
        if ($iban) {
            $url = $this->base_url . '/payout_accounts';

            $data = [
                'account_type' => 'bank',
                'properties' => [
                    'iban' => $iban
                ],
                'credentials' => [
                    'client_id' => $this->public_key,
                    'client_secret' => $this->secret
                ]
            ];

            try {
                $response = Http::post($url, json_encode($data), [
                    'headers' => $this->getHeaders()
                ]);

                if ($response) {
                    $response = json_decode($response, true);
        
                    if (isset($response['id'])) {
                        fn_moyasar_payment_update_settings([
                            'iban' => $iban,
                            'payout_id' => $response['id']
                        ]);
        
                        $success = $response['id'];
                    } else if (isset($response['errors']) && is_array($response['errors'])) {
                        $errors = $response['errors'];
                        $error = reset($errors);
    
                        // get errors first value
                        $message = reset($error);
                    } else if (isset($response['message'])) {
                        $message = $response['message'];
                    }
    
                    // Check if payout account already exists
                    if (isset($response['type']) && $response['type'] == 'payout_account_exists') {
                        $this->updateExistPayoutAccount($iban);
                        $success = true;
                    }
                }
            } catch (\Exception $e) {
                $success = false;
            }
        }

        return [$success, $message];
    }

    private function updateExistPayoutAccount($iban) {
        $response = Http::get($this->base_url . '/payout_accounts?iban=' . $iban, null, [
            'headers' => $this->getHeaders()
        ]);

        if ($response) {
            $response = json_decode($response, true);
    
            if (!empty($response['payout_accounts'])) {
                $payout_accounts = $response['payout_accounts'];
                $payout_account = reset($payout_accounts);
    
                fn_moyasar_payment_update_settings([
                    'iban' => $iban,
                    'payout_id' => $payout_account['id'],
                    'currency' => $payout_account['currency']
                ]);
            }
        }
    }

    public function createPayout($data) {
        $url = $this->base_url . '/payouts';

        $message = '';
        try {
            $response = Http::post($url, json_encode($data), [
                'headers' => $this->getHeaders()
            ]);

            if ($response) {
                $result = json_decode($response, true);
    
                if (isset($result['errors']) && is_array($result['errors'])) {
                    $errors = $result['errors'];
                    $error = reset($errors);
    
                    // get errors first value
                    $message = implode(', ', $error);
                    $result = false;
                } else if (isset($result['message'])) {
                    $message = $result['message'];
                }
            }
        } catch (\Exception $e) {
            $result = false;
        }

        return [$result, $message];
    }

    public function getPayout($id) {
        $url = $this->base_url . '/payouts/' . $id;

        $message = '';
        try {
            $response = Http::get($url, null, [
                'headers' => $this->getHeaders()
            ]);
            
            if ($response) {
                $response = json_decode($response, true);
            }

            $result = $response;
        } catch (\Exception $e) {
            $result = false;
        }

        return [$result, $message];
    }
}