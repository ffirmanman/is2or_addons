<?php

namespace Tygh {
    class Registry
    {
        public static function get($key)
        {
            if (strpos($key, 'currencies.') === 0) {
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

    require __DIR__ . '/../src/app/addons/amazon_payment_services/AmazonPaymentServices/Logger.php';
    require __DIR__ . '/../src/app/addons/amazon_payment_services/AmazonPaymentServices/Gateways/Gateway.php';
    require __DIR__ . '/../src/app/addons/amazon_payment_services/AmazonPaymentServices/Gateways/Tabby.php';

    $gateway = new \AmazonPaymentServices\Gateways\Tabby('Tabby', [
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

    $gateway->setOrderId(123);
    $gateway->setAmount(50);
    $gateway->setCustomerInfo([
        'customer_email' => 'otp.success@tabby.ai',
        'customer_name' => 'Test Buyer',
        'phone_number' => '+966 500-000-001',
    ]);

    $response = $gateway->processRequest();
    $params = $response['params'];

    assert($response['success'] === true);
    assert($params['command'] === 'PURCHASE');
    assert($params['payment_option'] === 'TABBY');
    assert($params['currency'] === 'SAR');
    assert($params['amount'] === '5000');
    assert($params['phone_number'] === '+966500000001');
    assert($params['customer_email'] === 'otp.success@tabby.ai');
    assert($gateway->isActive() === true);
    assert($gateway->integration_type === 'redirection');

    echo "Tabby gateway request test passed\n";
}
