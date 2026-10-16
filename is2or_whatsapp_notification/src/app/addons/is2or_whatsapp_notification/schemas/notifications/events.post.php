<?php

use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Whatsapp\WhatsappSchema;
use Tygh\Notifications\Transports\Whatsapp\WhatsappTransport;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$wa_message_schema = WhatsappSchema::create([
    'area'            => SiteArea::STOREFRONT,
    'from'            => 'company_orders_department',
    'to'              => DataValue::create('order_info.phone'),
    'company_id'      => DataValue::create('order_info.company_id'),
    'storefront_id'   => DataValue::create('order_info.storefront_id'),
    'language_code'   => DataValue::create('order_info.lang_code', CART_LANGUAGE),
    'template_code'   => 'is2or_whatsapp_notification', // Akan diupdate di loop
    'data_modifier'   => function (array $data) {
        // Bersihkan nomor telepon agar sesuai standar internasional (62)
        if (!empty($data['order_info']['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['order_info']['phone']);
            if (strpos($phone, '0') === 0) {
                $phone = '966' . substr($phone, 1);
            }
            $data['order_info']['phone'] = $phone;
        }
        return $data;
    }
]);

foreach (fn_get_simple_statuses() as $status_id => $status_description) {
    $status_id = strtolower($status_id);
    $event_id = "order.status_changed.{$status_id}";

    if (isset($schema[$event_id])) {
        $current_wa_schema = clone $wa_message_schema;
        $current_wa_schema->template_code = $event_id;
        $schema[$event_id]['receivers'][UserTypes::CUSTOMER][WhatsappTransport::getId()] = $current_wa_schema;
        if (fn_allowed_for('MULTIVENDOR')) {
            $schema[$event_id]['receivers'][UserTypes::VENDOR][WhatsappTransport::getId()] = $current_wa_schema;
        }
    }
}

if (Registry::get('addons.rma.status') === 'A') {
    foreach (fn_get_simple_statuses(STATUSES_RETURN) as $status_code => $status_description) {
        $status_code = strtolower($status_code);
        $event_id = "rma.status_changed.{$status_code}";

        if (isset($schema[$event_id])) {
            $rma_wa_schema = clone $wa_message_schema;
            $rma_wa_schema->template_code = $event_id;
            $schema[$event_id]['receivers'][UserTypes::CUSTOMER][WhatsappTransport::getId()] = $rma_wa_schema;
            if (fn_allowed_for('MULTIVENDOR')) {
                $schema[$event_id]['receivers'][UserTypes::VENDOR][WhatsappTransport::getId()] = $rma_wa_schema;
            }
        }
    }
}

if (isset($schema['order.updated'])) {
    $updated_wa_schema = clone $wa_message_schema;
    $updated_wa_schema->template_code = 'event.order.updated.name';
    $schema['order.updated']['receivers'][UserTypes::CUSTOMER]['whatsapp'] = $updated_wa_schema;
}

if (isset($schema['order.shipment_updated'])) {
    $updated_wa_schema = clone $wa_message_schema;
    $updated_wa_schema->template_code = 'shipment_products';
    $schema['order.shipment_updated']['receivers'][UserTypes::CUSTOMER]['whatsapp'] = $updated_wa_schema;
}

if (isset($schema['order.edp'])) {
    $updated_wa_schema = clone $wa_message_schema;
    $updated_wa_schema->template_code = 'event.order.edp.name';
    $schema['order.edp']['receivers'][UserTypes::CUSTOMER]['whatsapp'] = $updated_wa_schema;
}


return $schema;
