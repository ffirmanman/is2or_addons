<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;

class Is2orAmazonPayments extends AEntity
{
    public function create($params)
    {
        if (empty($params['order_id'])) {
            return ['status' => Response::STATUS_BAD_REQUEST, 'data' => ['message' => 'Missing order_id']];
        }

        $order_id = (int) $params['order_id'];
        $order_info = fn_get_order_info($order_id);

        if (empty($order_info) || empty($order_info['payment_method']['processor_params'])) {
            return ['status' => Response::STATUS_NOT_FOUND, 'data' => ['message' => 'Order/Payment not found']];
        }

        // Security: ensure the order belongs to the currently logged-in user
        if ((int) $order_info['user_id'] !== (int) $this->auth['user_id']) {
            return ['status' => Response::STATUS_UNAUTHORIZED, 'data' => ['message' => 'Order/Payment not found']];
        }

        $processor_params = $order_info['payment_method']['processor_params'];
        $gateway = new \AmazonPaymentServices\Gateways\Gateway('', $processor_params);

        $pf_order_id = !empty($order_info['repaid']) ? $order_id .'_'. $order_info['repaid'] : $order_id;
        $currency = $order_info['secondary_currency'] ?? $order_info['primary_currency'] ?? $gateway->getCurrency();
        
        $gateway->setOrderId($pf_order_id);
        $gateway->setCurrency($currency);
        $gateway->setAmount($order_info['total']);
        $merchant_reference = $gateway->generateReference($order_id);

        $user_id = (int)$this->auth['user_id'];

        $post_data = [
            'command'             => $gateway->getConfig('command'),
            'access_code'         => $gateway->getConfig('access_code'),
            'merchant_identifier' => $gateway->getConfig('merchant_identifier'),
            'merchant_reference'  => $merchant_reference,
            'amount'              => $gateway->getAmount(),
            'currency'            => $currency,
            'language'            => $gateway->getLanguage(),
            'customer_email'      => $order_info['email'],
            'customer_name'       => trim($order_info['firstname'] . ' ' . $order_info['lastname']),
            'order_description'   => "Order #{$order_id}",
            'return_url'          => fn_url("amazon_payment_services.callback?order_id=" . base64_encode($order_id), 'C'),
            'merchant_extra1'     => $order_id,
            'merchant_extra2'     => $user_id,
        ];

        $post_data['signature'] = $gateway->generateSignature($post_data);

        // DATABASE UPDATE: parent order
        db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", [
            'aps_reference' => $merchant_reference,
            'aps_gateway'   => 'cc', 
        ], $order_id);

        // SYNC: if this is a parent order, update all child orders with the same reference
        if (isset($order_info['is_parent_order']) && $order_info['is_parent_order'] == 'Y') {
            db_query(
                "UPDATE ?:orders SET aps_reference = ?s, aps_gateway = ?s WHERE parent_order_id = ?i",
                $merchant_reference,
                'cc',
                $order_id
            );
        }

        return [
            'status' => Response::STATUS_CREATED,
            'data'   => [
                'checkout_url'       => $gateway->getCheckoutUrl(),
                'post_params'        => $post_data,
                'webview_direct_url' => fn_url(
                    "amazon_payment_services.redirect_url?order_id=" . base64_encode($order_id) .
                    "&user_id=" . base64_encode($user_id),
                    'C'
                )
            ]
        ];
    }

    public function update($id, $params)
    {
        $order_id = (int) $id;
        $order_info = fn_get_order_info($order_id);

        if (empty($order_info) || empty($order_info['payment_method']['processor_params'])) {
            return array(
                'status' => Response::STATUS_NOT_FOUND,
                'data'   => array('message' => 'Order/Payment invalid')
            );
        }

        if ((int) $order_info['user_id'] !== (int) $this->auth['user_id']) {
            return array('status' => Response::STATUS_UNAUTHORIZED);
        }

        $aps_gateway = new \AmazonPaymentServices\Gateways\Gateway(
            '',
            $order_info['payment_method']['processor_params']
        );

        // Server-to-server check to Amazon
        $response = $aps_gateway->checkUpdateOrder(
            $order_info['aps_gateway'], 
            $order_info['aps_reference'], 
            $order_info['payment_info']
        );

        // Handle authorization success (capture)
        if (
            !empty($response['order_status']) &&
            $response['order_status'] == 'P' &&
            ($response['response_type'] ?? '') == 'AUTHORIZATION_SUCCESS'
        ) {
            $capture_amount = !empty($response['amount'])
                ? (float) $response['amount']
                : (float) $order_info['total'];

            list($capture_success, $capture_error, $capture_resp) = fn_amazon_payment_services_order_actions(
                $order_id,
                'capture',
                $capture_amount
            );

            if (!$capture_success) {
                $response['order_status'] = 'F';
                $response['response_message'] = $capture_error ?: 'Unable to capture authorized payment';
            } else {
                $response = array_merge($response, $capture_resp);
                $response['command'] = 'CAPTURE';
                $response['captured_amount'] = number_format($capture_amount, 2, '.', '');
                $response['generated_response_signature'] = $aps_gateway->generateSignature($capture_resp, true);
            }
        }

        $aps_gateway->setResponse($response);
        $final_res = $aps_gateway->processResponse();

        // ORDER FINALIZATION LOGIC
        if (!empty($response['order_status']) && $response['order_status'] == 'P') {

            // 1. If this is a parent order, finish all child orders first
            if (isset($order_info['is_parent_order']) && $order_info['is_parent_order'] == 'Y') {
                $child_order_ids = db_get_fields(
                    "SELECT order_id FROM ?:orders WHERE parent_order_id = ?i",
                    $order_id
                );

                foreach ($child_order_ids as $c_id) {
                    fn_finish_payment($c_id, $final_res['details'] ?? []);
                }
            }

            // 2. Finish the main order (parent or single order)
            fn_finish_payment($order_id, $final_res['details'] ?? []);

            return [
                'status' => Response::STATUS_OK,
                'data'   => [
                    'status'   => 'success',
                    'message'  => 'Payment verified and orders updated',
                    'order_id' => $order_id
                ]
            ];
        }

        return [
            'status' => Response::STATUS_OK,
            'data'   => [
                'status'  => 'failed',
                'message' => $response['response_message'] ?? 'Verification failed'
            ]
        ];
    }

    public function index($id = '', $params = array())
    {
        return array('status' => Response::STATUS_METHOD_NOT_ALLOWED);
    }

    public function delete($id)
    {
        return array('status' => Response::STATUS_METHOD_NOT_ALLOWED);
    }

    public function privileges()
    {
        return ['create' => true, 'update' => true];
    }

    public function privilegesCustomer()
    {
        return ['create' => true, 'update' => true];
    }
}
