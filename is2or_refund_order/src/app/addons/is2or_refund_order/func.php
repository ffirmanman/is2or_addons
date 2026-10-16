<?php
/*
 * (c) 2026 IS2OR
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Storage;
use Tygh\Registry;

/**
 * Retrieves evidence data from the is2or_refund_order table based on return_id
 * Used in both Admin and Frontend (rma.details)
 * * @param int $return_id
 * @return array
 */
function fn_get_is2or_evidences($return_id) {
    return db_get_array("SELECT * FROM ?:is2or_refund_order WHERE return_id = ?i", $return_id);
}

/**
 * Handles multiple file uploads when an RMA is created
 * Hook: rma_add_return_post
 */
function fn_is2or_refund_order_rma_add_return_post($return_id, $order_info, $return_data) {
    if (empty($return_id)) {
        return;
    }

    // Retrieve order and user information associated with the RMA
    $rma_data = db_get_row("SELECT order_id, user_id FROM ?:rma_returns WHERE return_id = ?i", $return_id);
    if (empty($rma_data)) {
        return;
    }

    // Define storage path
    $relative_path = 'refund/' . $return_id . '/';
    $full_path = fn_get_files_dir_path() . $relative_path;

    // Ensure directory exists and not duplicate files
    if (is_dir($full_path)) {
        $files_in_folder = glob($full_path . '*'); // Ambil semua file di folder tersebut
        foreach ($files_in_folder as $file) {
            if (is_file($file)) {
                @unlink($file); // Hapus file fisik
            }
        }
        // Opsional: Hapus record lama di database agar tidak berantakan
        db_query("DELETE FROM ?:is2or_refund_order WHERE return_id = ?i", $return_id);
    } else {
        fn_mkdir($full_path);
    }

    // Map input field names to evidence types
    $upload_configs = [
        'photo' => 'refund_evidence_photo',
        'video' => 'refund_evidence_video'
    ];

    foreach ($upload_configs as $type_key => $field_name) {
        $actual_key = '';
        if (isset($_FILES[$field_name]) && !empty($_FILES[$field_name]['name'])) {
            $actual_key = $field_name;
        } elseif (isset($_FILES[$field_name . '[]']) && !empty($_FILES[$field_name . '[]']['name'])) {
            $actual_key = $field_name . '[]';
        }

        if (!empty($actual_key)) {
            $files = $_FILES[$actual_key];

            if (!is_array($files['name'])) {
                $files = [
                    'name'     => [$files['name']],
                    'tmp_name' => [$files['tmp_name']],
                    'error'    => [$files['error']]
                ];
            }

            foreach ($files['name'] as $key => $original_name) {
                if ($files['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_path = $files['tmp_name'][$key];
                    $clean_name = fn_basename($original_name);
                    $file_name = time() . "_" . $key . "_" . $clean_name;
                    $destination = $full_path . $file_name;

                    if (move_uploaded_file($tmp_path, $destination)) {
                        $insert_data = [
                            'return_id' => (int) $return_id,
                            'order_id'  => (int) $rma_data['order_id'],
                            'user_id'   => (int) $rma_data['user_id'],
                            'filename'  => $file_name,
                            'file_type' => $type_key
                        ];
                        db_query("INSERT INTO ?:is2or_refund_order ?e", $insert_data);
                    }
                }
            }
        }
    }
}

/**
 * Main hook triggered when order status changes
 */
function fn_is2or_refund_order_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses, &$place_order)
{
    // Get data from setting addon
    $selected_statuses = Registry::get('addons.is2or_refund_order.tab_refunded_statuses');

    // Save multiple checkboxes
    if (!is_array($selected_statuses)) {
        $valid_statuses = explode(',', $selected_statuses);
    } else {
        $valid_statuses = array_keys($selected_statuses);
    }

    // Validation
    if (!in_array($status_to, $valid_statuses)) {
        return;
    }

    $refunded_status = db_get_field("
        SELECT A.status
        FROM ?:statuses A
        JOIN ?:status_descriptions B
        ON B.status_id = A.status_id
        WHERE A.type = 'O'
        AND LOWER(B.description) = 'refunded'
    ");

    if (empty($refunded_status)) {
        return;
    }

    // Trigger Check: Only proceed if status is changed to (Refunded)
    if ($status_to == $refunded_status) {
        $abort_validation = false;
        $warning_message = '';

        // 1. Admin validation
        if (Tygh::$app['session']['auth']['user_type'] !== 'A') {
            $abort_validation = true;
            $warning_message = 'Need admin permission to set this status';
        }

        // 2. Protection RMA
        if (!$abort_validation) {
            $return_data = db_get_row(
                "SELECT return_id, status FROM ?:rma_returns WHERE order_id = ?i ORDER BY return_id DESC LIMIT 1",
                $order_info['order_id']
            );

            if (empty($return_data)) {
                $abort_validation = true;
                $warning_message = 'Refund Aborted: No RMA request found for this order.';
            } elseif ($return_data['status'] !== 'A') {
                $abort_validation = true;
                $warning_message = 'Refund Aborted: RMA status is not "Approved".';
            }
        }

        //  Execution rollback
        if ($abort_validation) {
            fn_set_notification('W', __('warning'), $warning_message);
            return;
        }

        // 3. Double Refund Protection
        if (!empty($order_info['payment_info']['refund_status']) && $order_info['payment_info']['refund_status'] === 'Success') {
            fn_set_notification('W', __('warning'), 'Refund Aborted: Already successfully refunded.');
            return;
        }
    }

    $payment_method = $order_info['payment_method'];
    $processor_name = isset($payment_method['processor']) ? strtolower($payment_method['processor']) : '';

    if (empty($processor_name) || strpos($processor_name, 'amazon') === false) {
        return;
    }

    fn_is2or_amazon_api_refund($order_info, $payment_method, $status_from);
}

/**
 * Function to request a Refund via Amazon API
 */
function fn_is2or_amazon_api_refund($order_info, $processor_data, $status_from)
{
    $params_config = $processor_data['processor_params'];
    $gateway = new \AmazonPaymentServices\Gateways\Gateway('', $params_config);
    $url = $gateway->getServiceUrl();

    // Retrieve identifiers from payment information
    $fort_id = !empty($order_info['payment_info']['fort_id']) ? $order_info['payment_info']['fort_id'] : '';
    $merch_ref = !empty($order_info['payment_info']['merchant_reference']) ? $order_info['payment_info']['merchant_reference'] : $order_info['order_id'];

    if (empty($fort_id)) {
        return;
    }

    // Prepare Amazon Refund API parameters
    $params = [
        'command'             => 'REFUND',
        'access_code'         => $params_config['access_code'],
        'merchant_identifier' => $params_config['merchant_identifier'],
        'merchant_reference'  => $merch_ref,
        'amount'              => number_format($order_info['total'] * 100, 0, '.', ''),
        'currency'            => !empty($order_info['secondary_currency']) ? $order_info['secondary_currency'] : 'SAR',
        'language'            => $gateway->getLanguage(),
        'fort_id'             => $fort_id,
    ];

    $params['signature'] = $gateway->generateSignature($params);

    // Send POST request to Amazon Gateway
    $response = \Tygh\Http::post($url, json_encode($params), [
        'headers' => ['Content-Type: application/json']
    ]);

    $result = json_decode($response, true);

    // Verify if API call was successful using Amazon response codes
    if (is_array($result) && (isset($result['status']) && ($result['status'] == '06' || $result['response_code'] == '06000'))) {
        $new_payment_info = [
            'refund_status' => 'Success',
            'amazon_refund_id' => $result['fort_id'],
            'amazon_refund_time' => date('Y-m-d H:i:s')
        ];
        // Save refund details into the order information
        fn_update_order_payment_info($order_info['order_id'], $new_payment_info);
        fn_set_notification('N', __('notice'), 'Amazon Refund Successful.');
    } else {
        // Log error message and revert status
        $msg = isset($result['response_message']) ? $result['response_message'] : 'API Error';
        fn_set_notification('W', __('warning'), 'Amazon Refund Failed: ' . $msg);
    }
}

if (!defined('STATUSES_ORDER')) {
    define('STATUSES_ORDER', 'O');
}

function fn_settings_variants_addons_is2or_refund_order_tab_refunded_statuses() {
    return fn_is2or_refund_order_get_order_statuses();
}


function fn_is2or_refund_order_get_order_statuses() {
    // Ambil data status Order (O), Shipment (H), dan Return (R) sekaligus
    return db_get_hash_single_array(
        "SELECT s.status, sd.description 
         FROM ?:statuses AS s 
         LEFT JOIN ?:status_descriptions AS sd 
            ON sd.status_id = s.status_id 
         WHERE s.type IN (?a) 
            AND sd.lang_code = ?s 
         ORDER BY s.type, sd.description ASC",
        ['status', 'description'],
        [STATUSES_ORDER], // Inisial tipe status
        CART_LANGUAGE
    );
}
