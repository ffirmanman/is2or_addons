<?php

//-----------------------------------------------------------------------------------------------------------------------------------------
// Moyasar Functions
//-----------------------------------------------------------------------------------------------------------------------------------------
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Enum\ObjectStatuses;

//-----------------------------------------------------------------------------------------------------------------------------------------
function moyasar_install() {
    fn_clear_cache();
    fn_set_notification('W', __('warning'), __('text_installation_warning'));

    if (Registry::get('addons.is2or_vendor_payout') && Registry::get('addons.is2or_vendor_payout.status') == ObjectStatuses::ACTIVE) {
        $payment_id = db_get_field("SELECT id FROM ?:is2or_vendor_payout_payments WHERE addon=?s", 'moyasar_payment');
        if (!$payment_id) {
            db_query("INSERT INTO ?:is2or_vendor_payout_payments ?e", [
                'name' => 'Moyasar',
                'addon' => 'moyasar_payment'
            ]);
        }
    }
}

//-----------------------------------------------------------------------------------------------------------------------------------------
function moyasar_uninstall() {
    moyasar_updatePaymentStatus(ObjectStatuses::DISABLED);
}

//-----------------------------------------------------------------------------------------------------------------------------------------
function moyasar_updatePaymentStatus($new_status) {
    //disable payments
    $processor   = fn_get_processor_data_by_name('moyasar_payment_processor.php');
    $processorId = $processor['processor_id'] ?? null;
    if (!$processorId) {
        return;
    }

    $payemnts = fn_get_payment_by_processor($processorId);
    foreach ($payemnts as $key => $value) {
        $params = [
            'id'                => $key,
            'status'            => $new_status,
            'table'             => 'payments',
            'id_name'           => 'payment_id',
            'show_error_notice' => false
        ];
        fn_tools_update_status($params);
    }
}
//-----------------------------------------------------------------------------------------------------------------------------------------
function moyasar_disableWithError($err) {
    fn_set_notification('E', __('Error'), __('text_moyasar_disabled'));
    fn_set_notification('E', __('Error'), $err);

    fn_update_addon_status('moyasar', ObjectStatuses::DISABLED, false);

    return false;
}

//-----------------------------------------------------------------------------------------------------------------------------------------
function moyasar_disableAppleWithWarning($err) {
    fn_set_notification('W', __('warning'), __('text_ap_disabled'));
    fn_set_notification('W', __('warning'), $err);

    Settings::instance()->updateValue('isApplePayRegistered', 'N', 'moyasar');

    return true;
}

//-----------------------------------------------------------------------------------------------------------------------------------------
function moyasar_getPPResponse($orderId, $data, $isWebHook = false) {
    $status = [
        'Paid'    => 'P',
        'Failed'  => 'F',
        'Expired' => STATUS_CANCELED_ORDER,
        'Pending' => STATUS_INCOMPLETED_ORDER,
    ];
    fn_change_order_status($orderId, $status[$data->InvoiceStatus]);

    $pp_response['invoice_id']  = $data->InvoiceId;
    $pp_response['reason_text'] = $data->InvoiceError;
    if ($data->focusTransaction) {
        $pp_response ['transaction_id'] = $data->focusTransaction->PaymentId;
        $pp_response ['gateway']        = $data->focusTransaction->PaymentGateway;
    }

    return $pp_response;
}

//-----------------------------------------------------------------------------------------------------------------------------------------
function moyasar_getInputData($name, $type = INPUT_POST) {
    $value = filter_input($type, $name, FILTER_UNSAFE_RAW);
    return htmlspecialchars($value);
}

//-----------------------------------------------------------------------------------------------------------------------------------------
function moyasar_getRequestData($name) {
    $value = $_REQUEST[$name] ?? null;
    return htmlspecialchars($value);
}

function moyasar_write_log($message, $filename = 'moyasar.log')
{
    $log_dir = Registry::get('config.dir.logs'); // biasanya: var/logs/
    $file_path = $log_dir.$filename;

    if (is_array($message)) {
        $message = json_encode($message);
    }
    // Ubah array/object jadi string
    if (is_object($message)) {
        $message = print_r($message, true); // atau gunakan json_encode($message, JSON_PRETTY_PRINT)
    }

    $formatted = '['.date('Y-m-d H:i:s').'] '.$message.PHP_EOL;

    file_put_contents($file_path, $formatted, FILE_APPEND);
}
