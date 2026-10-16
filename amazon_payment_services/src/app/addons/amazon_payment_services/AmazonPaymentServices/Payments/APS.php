<?php

namespace AmazonPaymentServices\Payments;

use AmazonPaymentServices\Gateways\Gateway;
use Tygh\Addons\StorefrontRestApi\Payments\IRedirectionPayment;
use Tygh\Addons\StorefrontRestApi\Payments\RedirectionPaymentDetailsBuilder;
use Tygh\Common\OperationResult;

class APS implements IRedirectionPayment{
    protected $order_info = [];
    protected $auth_info = [];
    protected $payment_info = [];

    /** @var \Tygh\Addons\StorefrontRestApi\Payments\RedirectionPaymentDetailsBuilder $details_builder */
    protected $details_builder;

    /** @var \Tygh\Common\OperationResult $preparation_result */
    private $preparation_result;

    public function __construct()
    {
        $this->details_builder = new RedirectionPaymentDetailsBuilder();
        $this->preparation_result = new OperationResult();
    }

    public function setOrderInfo(array $order_info)
    {
        $this->order_info = $order_info;
        return $this;
    }

    public function setAuthInfo(array $auth_info)
    {
        $this->auth_info = $auth_info;
        return $this;
    }

    public function setPaymentInfo(array $payment_info)
    {
        $this->payment_info = $payment_info;
        return $this;
    }

    public function getDetails(array $request)
    {
        $gateway = new Gateway(
            $this->payment_info['gateway'],
            $this->order_info['payment_method']['processor_params'] ?? ''
        );

        $order_id = $this->order_info['order_id'];
        $user_id = $this->auth_info['user_id'];

        $gateway->setOrderId($order_id);
        $reference = $gateway->generateReference($order_id);
        $return_url = fn_url('amazon_payment_services.callback?order_id=' . base64_encode($order_id), 'C');
        $redirect_url = fn_url('amazon_payment_services.redirect_url?order_id=' . base64_encode($order_id) . '&user_id=' . base64_encode($user_id), 'C');
        // $gateway->setAmount($this->order_info['total']);

        // $params = [
        //     // 'service_command' => 'PAYMENT_LINK',
        //     'command' => 'AUTHORIZATION',
        //     'access_code' => $gateway->getConfig('access_code'),
        //     'merchant_identifier' => $gateway->getConfig('merchant_identifier'),
        //     'merchant_reference' => $gateway->generateReference($this->order_info['order_id']),
        //     'amount' => $gateway->getAmount(),
        //     'currency' => $this->order_info['secondary_currency'],
        //     'language' => $gateway->getLanguage(),
        //     'customer_email' => fn_get_user_email($this->auth_info['user_id']),
        //     'customer_name' => fn_get_user_name($this->auth_info['user_id']),
        //     // 'customer_phone' => $this->auth_info['phone'],
        //     // ISO 8601 format
        //     // 'request_expiry_date' => date('c', strtotime('+1 hour')),
        //     // 'notification_type' => 'NONE',
        //     'order_description' => "Order #{$this->order_info['order_id']}",
        //     // 'link_command' => 'AUTHORIZATION',
        //     'return_url' => $gateway->getReturnUrl(),
        //     // 'return_url' => fn_url('index.index'), // Debug
        // ];
        
        // $params['merchant_extra1'] = $this->order_info['order_id'];
        // $params['merchant_extra2'] = $this->auth_info['user_id'];

        // $gateway->setAdditionalRequestParams($params);

        // $params['signature'] = $gateway->generateSignature($params);
        
        // $response = $gateway->httpCall('POST', $params);

        // fn_log_event('general', 'runtime', [
        //     'message' => 'APS IRedirect Response: ' . json_encode($response),
        // ]);

        // parse the response
        // $response_data = json_decode($response, true);
        $this->preparation_result->setSuccess(true);
        
        $this->preparation_result->setData(
            $this->details_builder
                ->setMethod(RedirectionPaymentDetailsBuilder::GET)
                ->setPaymentUrl($redirect_url)
                ->setReturnUrl($return_url)
                ->setCancelUrl($return_url)
                ->asArray()
        );

        return $this->preparation_result;
    }
}