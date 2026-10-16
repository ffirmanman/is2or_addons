<?php
defined('BOOTSTRAP') or die('Access denied');

use Tygh\Enum\UserTypes;
use Tygh\Registry;

if ($mode == 'test_send') {
    $order_id = (int) $_REQUEST['order_id'];
    $order_info = fn_get_order_info($order_id);

    if (empty($order_info)) {
        fn_set_notification('E', __('error'), 'Order ID tidak ditemukan');
    } else {
        $status_id = strtolower($order_info['status']);
        $event_id = "order.status_changed.{$status_id}";

        /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
        $event_dispatcher = Tygh::$app['event.dispatcher'];

        // Dispatch event agar ditangkap oleh WhatsappTransport
        $event_dispatcher->dispatch(
            $event_id,
            ['order_info' => $order_info],
        );

        fn_set_notification('N', __('notice'), 'WhatsApp test push triggered via event.dispatcher: ' . $event_id);
    }
    return [CONTROLLER_STATUS_REDIRECT, "orders.details?order_id={$order_id}"];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $event_id = $_REQUEST['event_id'];
        $receiver = $_REQUEST['receiver'];
        $content = $_REQUEST['wa_template']['content'];

        db_query("REPLACE INTO ?:is2or_wa_templates ?e", [
            'status_id' => $event_id,
            'receiver'  => $receiver,
            'content'   => $content
        ]);

        return [CONTROLLER_STATUS_OK, "is2or_whatsapp_notification.manage?event_id={$event_id}&receiver={$receiver}"];
    }
}

if ($mode == 'manage') {
    $event_id = $_REQUEST['event_id'];
    $receiver = $_REQUEST['receiver'];

    // Pecah event_id (order.status_changed.p) untuk ambil kode statusnya (p)
    $event_parts = explode('.', $event_id);
    $status_code = strtoupper(end($event_parts)); // Jadi 'P'

    // Ambil nama status asli dari CS-Cart (misal: 'Paid' atau 'Processed')
    $status_data = fn_get_status_data($status_code, STATUSES_ORDER);
    $status_name = !empty($status_data['description']) ? $status_data['description'] : $status_code;

    if ($receiver == UserTypes::VENDOR) {
        $receiver_name = __('vendor');
    }else if ($receiver == UserTypes::ADMIN) {
        $receiver_name = __('admin');
    }else if ($receiver == UserTypes::CUSTOMER) {
        $receiver_name = __('customer');
    }

    $wa_template = db_get_row(
        "SELECT * FROM ?:is2or_wa_templates WHERE status_id = ?s AND receiver = ?s",
        $event_id,
        $receiver
    );

    // PERBAIKAN: Gunakan Registry::get('config.dir.app')
    $variables = [
        'order' => [
            'alias' => 'o',
            'attributes' => [
                'order_id', 'total', 'subtotal', 'status', 'notes'
            ]
        ],
        'user' => [
            'alias' => 'u',
            'attributes' => [
                'firstname', 'lastname', 'email', 'phone'
            ]
        ],
        'shipping' => [
            'alias' => 's',
            'attributes' => [
                'shipping_method', 'tracking_number'
            ]
        ]
    ];

    Tygh::$app['view']->assign([
        'wa_template'  => $wa_template,
        'event_id'     => $event_id,
        'receiver'     => $receiver,
        'status_name'  => $status_name,
        'receiver_name'=> $receiver_name,
        'variables'    => $variables
    ]);
}
