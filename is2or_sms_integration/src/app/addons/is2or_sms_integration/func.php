<?php

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Registry;

if (!defined('IS2OR_SMS_TWILIO_API_URL')) {
    define('IS2OR_SMS_TWILIO_API_URL', 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json');
}

/**
 * Hook: change_order_status
 *
 * NOTE: The exact parameter order/count of the "change_order_status" hook can vary slightly
 * between CS-Cart core versions/patches. Please verify against the fn_change_order_status()
 * call in app/functions/fn.orders.php of your 4.19 codebase and adjust the signature below
 * if needed.
 *
 * @param string $status_to        New order status code
 * @param string $status_from      Previous order status code
 * @param int    $order_id         Order ID
 * @param array  $force_notification
 * @param array  $order_info       Order data (by reference)
 * @param array  $data
 */
function fn_is2or_sms_integration_change_order_status($status_to, $status_from, &$order_info, $force_notification, $order_statuses, &$place_order)
{
    if ($status_to === $status_from) {
        return;
    }

    if (Registry::get('addons.is2or_sms_integration.enabled') != 'Y') {
        return;
    }

    if (Registry::get('addons.is2or_sms_integration.order_status_sms_enabled') != 'Y') {
        return;
    }

    $allowed_statuses = fn_is2or_sms_integration_parse_status_list(
        Registry::get('addons.is2or_sms_integration.order_status_sms_statuses')
    );

    if (!empty($allowed_statuses) && !in_array($status_to, $allowed_statuses, true)) {
        return;
    }

    $order_id = isset($order_info['order_id']) ? (int) $order_info['order_id'] : 0;
    
    if (empty($order_id)) {
        return;
    }

    $full_order_info = fn_get_order_info($order_id);
    
    if (empty($full_order_info)) {
        $full_order_info = $order_info;
    }

    $phone = fn_is2or_sms_integration_get_order_phone($full_order_info);
    
    if ($phone === '') {
        fn_is2or_sms_integration_log(
            $order_id,
            '',
            '',
            'skipped',
            'No phone number found on the order.'
        );
        return;
    }

    $message = fn_is2or_sms_integration_build_order_message($full_order_info, $status_to);

    fn_is2or_sms_integration_send_sms($phone, $message, $order_id);
}

/**
 * Builds the SMS message body for an order status notification using the configured template.
 *
 * @param array  $order_info
 * @param string $status_to
 *
 * @return string
 */
function fn_is2or_sms_integration_build_order_message($order_info, $status_to)
{
    $template = Registry::get('addons.is2or_sms_integration.order_status_sms_template');
    if (empty($template)) {
        $template = 'Hi {firstname}, your order #{order_id} status has been updated to: {status}.';
    }

    $status_name = fn_get_status_data($status_to, 'O');
    $status_label = !empty($status_name['description']) ? $status_name['description'] : $status_to;

    $replacements = array(
        '{order_id}'      => !empty($order_info['order_id']) ? $order_info['order_id'] : '',
        '{status}'        => $status_label,
        '{firstname}'     => !empty($order_info['firstname']) ? $order_info['firstname'] : '',
        '{lastname}'      => !empty($order_info['lastname']) ? $order_info['lastname'] : '',
        '{company_name}'  => fn_is2or_sms_integration_get_company_name($order_info),
        '{shop_name}'     => Registry::get('settings.Company.company_name'),
    );

    return strtr($template, $replacements);
}

/**
 * Attempts to resolve the vendor/company name related to the order, falling back
 * to the storefront company name.
 *
 * @param array $order_info
 *
 * @return string
 */
function fn_is2or_sms_integration_get_company_name($order_info)
{
    if (!empty($order_info['company_id']) && Registry::get('runtime.company_id') !== false) {
        $company_name = db_get_field(
            'SELECT company FROM ?:companies WHERE company_id = ?i',
            $order_info['company_id']
        );
        if (!empty($company_name)) {
            return $company_name;
        }
    }

    return (string) Registry::get('settings.Company.company_name');
}

/**
 * Tries several common order/profile field keys to find a usable phone number.
 *
 * @param array $order_info
 *
 * @return string
 */
function fn_is2or_sms_integration_get_order_phone($order_info)
{
    $candidate_keys = array('phone', 'b_phone', 's_phone');

    foreach ($candidate_keys as $key) {
        if (!empty($order_info[$key])) {
            return trim($order_info[$key]);
        }
    }

    if (!empty($order_info['fields']) && is_array($order_info['fields'])) {
        foreach ($order_info['fields'] as $label => $value) {
            if (stripos($label, 'phone') !== false && !empty($value)) {
                return trim($value);
            }
        }
    }

    return '';
}

/**
 * Parses a comma-separated list of order status codes from the addon settings.
 *
 * @param string $raw
 *
 * @return array
 */
function fn_is2or_sms_integration_parse_status_list($raw)
{
    if (empty($raw)) {
        return array();
    }

    $list = array_map('trim', explode(',', $raw));

    return array_filter($list, function ($item) {
        return $item !== '';
    });
}

/**
 * Normalizes a phone number into E.164-ish format using the configured default country code
 * when the number is given in local format (leading 0).
 *
 * @param string $phone
 *
 * @return string
 */
function fn_is2or_sms_integration_normalize_phone($phone)
{
    $phone = preg_replace('/[^0-9+]/', '', (string) $phone);

    if ($phone === '') {
        return '';
    }

    if (strpos($phone, '+') === 0) {
        return $phone;
    }

    $default_country_code = trim((string) Registry::get('addons.is2or_sms_integration.default_country_code'));
    if ($default_country_code === '') {
        $default_country_code = '+966';
    }

    if (strpos($phone, '0') === 0) {
        $phone = ltrim($phone, '0');
    }

    return $default_country_code . $phone;
}

/**
 * Sends an SMS through the Twilio REST API and logs the attempt.
 *
 * @param string $phone     Destination phone number (local or international format)
 * @param string $message   Message body
 * @param int    $order_id  Optional related order ID, for logging purposes
 *
 * @return bool True on success, false otherwise
 */
function fn_is2or_sms_integration_send_sms($phone, $message, $order_id = 0)
{
    if (Registry::get('addons.is2or_sms_integration.enabled') != 'Y') {
        fn_is2or_sms_integration_log($order_id, $phone, $message, 'disabled', 'Addon is disabled.');
        return false;
    }

    $account_sid = trim((string) Registry::get('addons.is2or_sms_integration.twilio_account_sid'));
    $auth_token  = trim((string) Registry::get('addons.is2or_sms_integration.twilio_auth_token'));
    $from_number = trim((string) Registry::get('addons.is2or_sms_integration.twilio_from_number'));

    if ($account_sid === '' || $auth_token === '' || $from_number === '') {
        fn_is2or_sms_integration_log($order_id, $phone, $message, 'failed', 'Twilio is not configured (missing SID/Token/From number).');
        return false;
    }

    $to_number = fn_is2or_sms_integration_normalize_phone($phone);
    if ($to_number === '') {
        fn_is2or_sms_integration_log($order_id, $phone, $message, 'failed', 'Invalid destination phone number.');
        return false;
    }

    $url = sprintf(IS2OR_SMS_TWILIO_API_URL, $account_sid);

    $post_fields = http_build_query(array(
        'To'   => $to_number,
        'From' => $from_number,
        'Body' => $message,
    ));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response    = curl_exec($ch);
    $curl_errno  = curl_errno($ch);
    $curl_error  = curl_error($ch);
    $http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_errno) {
        fn_is2or_sms_integration_log($order_id, $to_number, $message, 'failed', 'cURL error: ' . $curl_error);
        return false;
    }

    $decoded = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300) {
        $log_status = 'sent';
        $success    = true;
    } else {
        $log_status = 'failed';
        $success    = false;
    }

    $log_response = is_array($decoded) ? json_encode($decoded) : (string) $response;

    if (Registry::get('addons.is2or_sms_integration.debug_mode') == 'Y') {
        fn_log_event('is2or_sms_integration', 'sms', array(
            'order_id'  => $order_id,
            'phone'     => $to_number,
            'http_code' => $http_code,
            'response'  => $log_response,
        ));
    }

    fn_is2or_sms_integration_log($order_id, $to_number, $message, $log_status, $log_response);

    return $success;
}

/**
 * Inserts a row into the SMS log table.
 *
 * @param int    $order_id
 * @param string $phone
 * @param string $message
 * @param string $status
 * @param string $response
 *
 * @return int Inserted log id
 */
function fn_is2or_sms_integration_log($order_id, $phone, $message, $status, $response)
{
    return db_query(
        'INSERT INTO ?:is2or_sms_logs ?e',
        array(
            'order_id'   => (int) $order_id,
            'phone'      => (string) $phone,
            'message'    => (string) $message,
            'status'     => (string) $status,
            'response'   => (string) $response,
            'created_at' => TIME,
        )
    );
}

/**
 * Retrieves SMS log entries for the admin "Manage" page.
 *
 * @param array $params
 * @param int   $items_per_page
 *
 * @return array
 */
function fn_is2or_sms_integration_get_logs($params, $items_per_page = 20)
{
    $params += array(
        'page' => 1,
    );

    $page = max(1, (int) $params['page']);
    $limit = max(1, (int) $items_per_page);
    $offset = ($page - 1) * $limit;

    $logs = db_get_array(
        'SELECT * FROM ?:is2or_sms_logs ORDER BY created_at DESC LIMIT ?i, ?i',
        $offset,
        $limit
    );

    $total_items = (int) db_get_field('SELECT COUNT(*) FROM ?:is2or_sms_logs');

    $search = array(
        'page'          => $page,
        'total_items'   => $total_items,
        'items_per_page' => $limit,
    );

    return array($logs, $search);
}


/* =======================================================================
 * LOGIN OTP (2-factor login: email + SMS OTP)
 * ======================================================================= */

/**
 * Generates a random numeric OTP code.
 *
 * @param int $length
 *
 * @return string
 */
function fn_is2or_sms_integration_generate_otp_code($length = 6)
{
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= (string) random_int(0, 9);
    }

    return $code;
}

/**
 * Looks up a phone number for the given registered user.
 *
 * NOTE: CS-Cart stores the customer's phone number on the `?:user_profiles` table.
 * The exact column name(s) can differ slightly depending on the profile fields
 * configured on your store (commonly `phone`, sometimes split into `b_phone` / `s_phone`
 * for billing/shipping). Please verify the actual column(s) on your DB and adjust
 * this query if needed.
 *
 * @param int $user_id
 *
 * @return string
 */
function fn_is2or_sms_integration_get_user_phone($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return '';
    }

    $phone = db_get_field(
        "SELECT phone FROM ?:users WHERE user_id = ?i AND phone != '' ORDER BY user_id ASC LIMIT 1",
        $user_id
    );

    
    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] LOLOS phone' . $phone
    );

    if (!empty($phone)) {
        return trim($phone);
    }

    // Fallback for stores that keep separate billing / shipping phone columns.
    foreach (array('b_phone', 's_phone') as $column) {
        $phone = db_get_field(
            "SELECT {$column} FROM ?:user_profiles WHERE user_id = ?i AND {$column} != '' ORDER BY profile_id ASC LIMIT 1",
            $user_id
        );
        if (!empty($phone)) {
            return trim($phone);
        }
    }
    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] LOLOS 222 phone' . $phone
    );

    return '';
}

/**
 * Generates a new OTP code for the given user, stores it, and sends it via SMS.
 * Any previously unused codes for this user are invalidated first.
 *
 * @param int    $user_id
 * @param string $phone
 *
 * @return bool True if the SMS was dispatched successfully.
 */
function fn_is2or_sms_integration_send_login_otp($user_id, $phone)
{
    
    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] MASUK fn_is2or_sms_integration_send_login_otp  ID =  ' . $user_id . ' | PHONE = ' . $phone
    ); 
    $ttl_minutes = (int) Registry::get('addons.is2or_sms_integration.login_otp_ttl_minutes');
    if ($ttl_minutes <= 0) {
        $ttl_minutes = 5;
    }

    db_query(
        "UPDATE ?:is2or_sms_otp_codes SET is_used = 'Y' WHERE user_id = ?i AND is_used = 'N'",
        $user_id
    );

    $code = fn_is2or_sms_integration_generate_otp_code();
    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] MASUK fn_is2or_sms_integration_generate_otp_code  code =  ' . $code
    ); 

    db_query(
        'INSERT INTO ?:is2or_sms_otp_codes ?e',
        array(
            'user_id'    => (int) $user_id,
            'code'       => $code,
            'phone'      => (string) $phone,
            'is_used'    => 'N',
            'expires_at' => TIME + ($ttl_minutes * 60),
            'created_at' => TIME,
        )
    );

    $template = Registry::get('addons.is2or_sms_integration.login_otp_sms_template');
    if (empty($template)) {
        $template = 'Your login verification code is {otp_code}. It is valid for {minutes} minutes.';
    }

    $message = strtr($template, array(
        '{otp_code}' => $code,
        '{minutes}'  => $ttl_minutes,
    ));

    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] OTP message generated | ' .
        'code=' . $code . ' | ' .
        'ttl_minutes=' . $ttl_minutes . ' | ' .
        'message=' . $message
    );

    return fn_is2or_sms_integration_send_sms($phone, $message, 0);
}

/**
 * Verifies an OTP code entered by the user against the latest unused, unexpired code
 * generated for that user. Marks the code as used on success.
 *
 * @param int    $user_id
 * @param string $code
 *
 * @return bool
 */
function fn_is2or_sms_integration_verify_login_otp($user_id, $code)
{
    $user_id = (int) $user_id;
    $code    = trim((string) $code);

    if ($user_id <= 0 || $code === '') {
        return false;
    }

    $row = db_get_row(
        "SELECT * FROM ?:is2or_sms_otp_codes WHERE user_id = ?i AND code = ?s AND is_used = 'N' ORDER BY id DESC LIMIT 1",
        $user_id,
        $code
    );

    if (empty($row)) {
        return false;
    }

    if ((int) $row['expires_at'] < TIME) {
        return false;
    }

    db_query("UPDATE ?:is2or_sms_otp_codes SET is_used = 'Y' WHERE id = ?i", $row['id']);

    return true;
}

/**
 * Puts the just-authenticated user's session into a "pending OTP verification" state:
 * the user is downgraded back to a guest session (so the store treats them as logged out)
 * while we remember who they are so we can restore the full session once the OTP is confirmed.
 *
 * NOTE: this is a lightweight downgrade (it only clears the `user_id` marker used by
 * CS-Cart to detect a logged-in customer). Please review against your CS-Cart 4.19 core
 * to confirm no other session keys need to be reset for a fully "logged out" state on
 * your build.
 *
 * @param int $user_id
 *
 * @return void
 */
function fn_is2or_sms_integration_start_pending_login($user_id)
{
    $_SESSION['is2or_sms_otp_pending'] = array(
        'user_id'    => (int) $user_id,
        'started_at' => TIME,
    );

    $_SESSION['auth']['user_id'] = 0;
}

/**
 * Restores the full authenticated session for the user who just passed OTP verification,
 * and clears the pending state.
 *
 * NOTE: fn_fill_auth() is the same internal CS-Cart helper used to finalize a login
 * session (e.g. for social login / "login as customer"). Please confirm its exact
 * signature in app/functions/fn.control.php for your 4.19 build; adjust the call below
 * if the parameter order differs.
 *
 * @return bool
 */
function fn_is2or_sms_integration_complete_pending_login()
{
    if (empty($_SESSION['is2or_sms_otp_pending']['user_id'])) {
        return false;
    }

    $user_id = (int) $_SESSION['is2or_sms_otp_pending']['user_id'];

    fn_fill_auth($_SESSION['auth'], array('user_id' => $user_id), true, true);

    unset($_SESSION['is2or_sms_otp_pending']);

    return true;
}

/**
 * Cancels a pending OTP login (e.g. user navigates away / cancels), fully logging them out.
 *
 * @return void
 */
function fn_is2or_sms_integration_cancel_pending_login()
{
    $auth = &Tygh::$app['session']['auth'];
    fn_user_logout($auth);
    
    unset($_SESSION['is2or_sms_otp_pending']);
    
    $_SESSION['auth']['user_id'] = 0;
    if (isset(Tygh::$app['session']['auth'])) {
        Tygh::$app['session']['auth']['user_id'] = 0;
    }
    
    $_SESSION['cart'] = array(
        'products' => array()
    );
    
    $_SESSION['wishlist'] = array(
        'products' => array()
    );
    
    if (isset($_SESSION['comparison_list'])) {
        unset($_SESSION['comparison_list']);
    }
    
    if (isset($_SESSION['recently_viewed_products'])) {
        unset($_SESSION['recently_viewed_products']);
    }
}

/**
 * @param int    $user_id
 * @param mixed  $cu_id
 * @param array  $udata
 * @param array  &$auth   Session auth data, by reference
 * @param string $condition
 * @param string $result  LOGIN_STATUS_OK on success
 */
function fn_is2or_sms_integration_login_user_post($user_id, $cu_id, $udata, &$auth, $condition, $result)
{
    if (function_exists('fn_is2or_custom_debug_log')) {
        fn_is2or_custom_debug_log(
            '[IS2OR_SMS_DEBUG] login_user_post HOOK fired. user_id=' . $user_id
            . ' result=' . $result . ' AREA=' . (defined('AREA') ? AREA : 'undefined')
        );
    }

    if ($result != LOGIN_STATUS_OK || empty($user_id)) {
        fn_is2or_custom_debug_log(
            '[IS2OR_SMS_DEBUG] FAILED LOGIN_STATUS_OK'
        );
        return;
    }

    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] LOLOS LOGIN_STATUS_OK'
    );
    
    if (!defined('AREA') || AREA != 'C') {
        fn_is2or_custom_debug_log(
            '[IS2OR_SMS_DEBUG] FAILED AREA'
        );
        return;
    }


    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] LOLOS AREA'
    );
    if (Registry::get('addons.is2or_sms_integration.login_otp_mode') != 'email_and_otp') {
        fn_is2or_custom_debug_log(
            '[IS2OR_SMS_DEBUG] FAILED email_and_otp'
        );
        return;
    }
    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] LOLOS email_and_otp'
    );

    if (!empty($_SESSION['is2or_sms_otp_pending']['user_id']) && $_SESSION['is2or_sms_otp_pending']['user_id'] == $user_id) {
        fn_is2or_custom_debug_log(
            '[IS2OR_SMS_DEBUG] FAILED is2or_sms_otp_pending (Already pending)'
        );
        
        $redirect_url = fn_url('is2or_sms_integration.otp_verify');
        if (defined('AJAX_REQUEST')) {
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            exit;
        } else {
            fn_redirect($redirect_url);
        }
        return;
    }
    
    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] LOLOS is2or_sms_otp_pending'
    );

    $phone = fn_is2or_sms_integration_get_user_phone($user_id);
    fn_is2or_custom_debug_log(
        '[IS2OR_SMS_DEBUG] LOLOS akhir phone' . $phone
    );

    if ($phone === '') {
        fn_is2or_sms_integration_log(
            0,
            '',
            '',
            'otp_blocked',
            'User #' . $user_id . ' has no phone number on file; login was blocked because OTP cannot be delivered.'
        );

        $auth['user_id'] = 0;
        $_SESSION['auth']['user_id'] = 0;

        return;
    }

    $sent = fn_is2or_sms_integration_send_login_otp($user_id, $phone);

    if (!$sent) {
        fn_is2or_sms_integration_log(
            0, $phone, '', 'otp_send_failed',
            'Failed to send login OTP SMS for user #' . $user_id . '. Login is still blocked (fail-closed); user can retry via Resend.'
        );
    }

    if (isset($_SESSION['auth']) && is_array($_SESSION['auth'])) {
        $_SESSION['auth']['user_id'] = 0;
    } else {
        $_SESSION['auth'] = array(
            'user_id'   => 0,
            'user_type' => 'C',
            'area'      => 'C',
        );
    }

    $auth['user_id'] = 0;

    $_SESSION['is2or_sms_otp_pending'] = array(
        'user_id'    => (int) $user_id,
        'started_at' => TIME,
    );

    if (function_exists('fn_is2or_custom_debug_log')) {
        fn_is2or_custom_debug_log(
            '[IS2OR_SMS_DEBUG] pending OTP set for user_id=' . $user_id
            . ' | session auth user_id now=' . (isset($_SESSION['auth']['user_id']) ? $_SESSION['auth']['user_id'] : 'unset')
        );
    }

    $redirect_url = fn_url('is2or_sms_integration.otp_verify');

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
        exit;
    } else {
        fn_redirect($redirect_url);
    }
}

if (!function_exists('fn_is2or_custom_debug_log')) {
    function fn_is2or_custom_debug_log($message) {
        $log_file = DIR_ROOT . '/var/log/is2or_ai_integration.log';
        $log_dir = dirname($log_file);

        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
    }
}