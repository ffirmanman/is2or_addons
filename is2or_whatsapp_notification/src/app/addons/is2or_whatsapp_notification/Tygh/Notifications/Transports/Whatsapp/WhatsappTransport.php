<?php
namespace Tygh\Notifications\Transports\Whatsapp;

use Tygh\Exceptions\DeveloperException;
use Tygh\Http;
use Tygh\Notifications\Transports\BaseMessageSchema;
use Tygh\Notifications\Transports\ITransport;
use Tygh\Registry;

class WhatsappTransport implements ITransport
{
    public const ID = 'whatsapp';

    public static function getId()
    {
        return self::ID;
    }

    public function process(BaseMessageSchema $schema, array $receiver_search_conditions)
    {
        if (!$schema instanceof WhatsappSchema) {
            throw new DeveloperException('Input data should be instance of WhatsappSchema');
        }
        $data = $schema->data;
        $to = $schema->to;

        $template_key = $schema->template_code;

        if (empty($data['order_info']['order_id'])) {
            return false;
        }

        $order = $data['order_info'];

        $receiver = $schema->area;

        $wa_template = db_get_row(
            "SELECT content FROM ?:is2or_wa_templates WHERE status_id = ?s AND receiver = ?s",
            $template_key,
            $receiver
        );


        if (empty($wa_template['content'])) {
            return false;
        }

        $message_text = $wa_template['content'];

        // 4. Parser Variabel (Hardcoded)
        $replace = [
            '{{ o.order_id }}'   => $order['order_id'],
            '{{ o.total }}'      => fn_format_price($order['total']),
            '{{ o.subtotal }}'   => fn_format_price($order['subtotal']),
            '{{ o.status }}'     => $order['status'],
            '{{ o.notes }}'      => !empty($order['notes']) ? $order['notes'] : '-',
            '{{ u.firstname }}'  => $order['firstname'],
            '{{ u.lastname }}'   => $order['lastname'],
            '{{ u.email }}'      => $order['email'],
            '{{ u.phone }}'      => $order['phone'],
            '{{ s.shipping_method }}' => !empty($order['shipping']) ? implode(', ', array_column($order['shipping'], 'shipping')) : '',
        ];

        $message_text = str_replace(array_keys($replace), array_values($replace), $message_text);
        if (!empty($to) && !empty($message_text)) {
            return $this->sendRequest($to, $message_text);
        }

        return false;
    }

    protected function sendRequest($phone, $message)
    {
        $api_key = Registry::get('addons.is2or_whatsapp_notification.api_key');
        $phone_id = Registry::get('addons.is2or_whatsapp_notification.phone_number_id');

        if (empty($api_key) || empty($phone_id)) {
            return false;
        }

        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        if (strpos($clean_phone, '0') === 0) {
            $clean_phone = '966' . substr($clean_phone, 1);
        } elseif (strpos($clean_phone, '966') !== 0) {
            $clean_phone = '966' . $clean_phone;
        }

        $url = "https://graph.facebook.com/v17.0/{$phone_id}/messages";

        $data = json_encode([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $clean_phone,
            'type'              => 'text',
            'text'              => ['body' => $message]
        ]);

        $headers = [
            "Authorization: Bearer {$api_key}",
            "Content-Type: application/json"
        ];

        $response = Http::post($url, $data, [
            'headers' => $headers,
        ]);

        if (!$response) {
            return false;
        }

        $res_data = json_decode($response, true);

        if (isset($res_data['error'])) {
            fn_log_event('general', 'whatsapp_error', [
                'to'      => $clean_phone,
                'error'   => $res_data['error']['message'],
                'full_res'=> $res_data
            ]);
            return false;
        }

        return true;
    }
}
