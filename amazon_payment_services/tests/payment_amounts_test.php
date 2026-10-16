<?php

namespace Tygh {
    class Registry
    {
        public static function get($key)
        {
            if ($key === 'currencies.SAR.coefficient' || strpos($key, 'currencies.') === 0) {
                return 1;
            }

            return null;
        }
    }
}

namespace {
    define('DIR_ROOT', sys_get_temp_dir());
    define('CART_PRIMARY_CURRENCY', 'SAR');
    define('CART_SECONDARY_CURRENCY', 'SAR');
    define('DESCR_SL', 'en');
    define('AREA', 'C');
    define('PRODUCT_NAME', 'CS-Cart');
    define('PRODUCT_VERSION', '4.test');
    define('PRODUCT_EDITION', 'MULTIVENDOR');

    function __($key, $params = [])
    {
        return $key;
    }

    function fn_url($url, $area = '', $protocol = '')
    {
        return $url;
    }

    function expect($condition, $message)
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    require __DIR__ . '/../src/app/addons/amazon_payment_services/AmazonPaymentServices/Logger.php';
    require __DIR__ . '/../src/app/addons/amazon_payment_services/AmazonPaymentServices/Gateways/Gateway.php';

    class CheckStatusGateway extends \AmazonPaymentServices\Gateways\Gateway
    {
        private $stubResponse;

        public function setStubResponse(array $stubResponse)
        {
            $this->stubResponse = $stubResponse;
        }

        public function httpCall($method, $params)
        {
            return $this->stubResponse;
        }
    }

    $gateway = new CheckStatusGateway('', [
        'enabled' => 'Y',
        'merchant_identifier' => 'Merchant123',
        'access_code' => 'Access123',
        'request_sha_phrase' => 'request',
        'response_sha_phrase' => 'response',
        'sha_type' => 'sha256',
        'command' => 'AUTHORIZATION',
        'currency' => 'F',
        'sandbox_mode' => 'Y',
    ]);

    $gateway->setCurrency('SAR');
    $gateway->setAmount(1);

    expect($gateway->getAmount() === '100', 'SAR 1.00 must be sent to APS as 100 minor units');

    $gateway->setStubResponse([
        'transaction_code' => '02000',
        'transaction_status' => '02',
        'response_code' => '12000',
        'fort_id' => '123456789',
        'authorized_amount' => '100',
        'captured_amount' => '0',
        'refunded_amount' => '0',
        'transaction_message' => 'Success',
    ]);

    $details = $gateway->checkUpdateOrder('cc', 'ORDER_REF', []);

    expect($details['response_type'] === 'AUTHORIZATION_SUCCESS', 'Expected authorization status');
    expect($details['amount'] === '1.00', 'CHECK_STATUS authorized_amount must be stored as SAR major units');
    expect(empty($details['captured_amount']), 'Uncaptured authorization should not store a captured amount');

    $gateway->setStubResponse([
        'transaction_code' => '04000',
        'transaction_status' => '04',
        'response_code' => '12000',
        'fort_id' => '123456789',
        'authorized_amount' => '100',
        'captured_amount' => '100',
        'refunded_amount' => '0',
        'transaction_message' => 'Success',
    ]);

    $details = $gateway->checkUpdateOrder('cc', 'ORDER_REF', []);

    expect($details['response_type'] === 'CAPTURE_SUCCESS', 'Expected capture status');
    expect($details['amount'] === '1.00', 'CHECK_STATUS authorized_amount after capture must be stored as SAR major units');
    expect($details['captured_amount'] === '1.00', 'CHECK_STATUS captured_amount must be stored as SAR major units');

    echo "APS amount conversion test passed\n";
}
