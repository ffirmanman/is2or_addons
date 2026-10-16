<?php

defined('BOOTSTRAP') or die('Access denied');

/**
 * Hook: sucess_user_login
 * Fires right after a user successfully authenticates.
 * Records/updates a row in our own tracking table — independent of
 * whatever session storage backend (Database/Redis/Memcached) is configured.
 */
function fn_is2or_login_activity_sucess_user_login(&$udata, &$auth)
{
    $user_id = (int) ($auth['user_id'] ?? 0);
    if ($user_id <= 0) {
        return;
    }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fingerprint = !empty($ua) ? md5($ua) : md5('unknown-' . uniqid('', true));
    $device = fn_is2or_login_activity_parse_ua($ua);
    $session_id = Tygh::$app['session']->getID();
    $ip = fn_is2or_login_activity_get_ip();

    if (!empty($ua)) {
        Tygh::$app['session']['is2or_ua'] = $ua;
        Tygh::$app['session']['is2or_device_fp'] = $fingerprint;
    }

    $data = [
        'user_id'            => $user_id,
        'session_id'         => $session_id,
        'device_fingerprint' => $fingerprint,
        'user_agent'         => substr($ua, 0, 255),
        'device_type'        => $device['type'],
        'device_name'        => $device['name'],
        'browser'            => $device['browser'],
        'os'                 => $device['os'],
        'ip_address'         => $ip,
        'login_at'           => TIME,
        'last_active_at'     => TIME,
        'logged_out_at'      => null,
        'is_active'          => 1,
    ];

    // Upsert: one row per (user_id, device_fingerprint) — re-logging in
    // from the same browser/device refreshes the existing row instead of
    // creating duplicates.
    db_query(
        'REPLACE INTO ?:is2or_login_sessions ?e',
        $data
    );
}

/**
 * Hook: user_logout
 * Fires when a user logs out via the standard CS-Cart logout button.
 * Marks the current session row in is2or_login_sessions as inactive.
 * This runs BEFORE the session is destroyed, so we can still access session data.
 *
 * @param array $auth   Auth data (will be cleared after this hook)
 * @param int   $user_id The user ID being logged out
 * @param string $area  Area ('A' for admin, 'C' for customer)
 */
function fn_is2or_login_activity_user_logout($auth, $user_id, $area)
{
    $user_id = (int) ($user_id ?: ($auth['user_id'] ?? 0));
    if ($user_id <= 0) {
        return;
    }

    // Get the device fingerprint stored during login (in sucess_user_login)
    $fingerprint = Tygh::$app['session']['is2or_device_fp'] ?? '';
    if (empty($fingerprint)) {
        // Fallback: use current User-Agent to compute fingerprint
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (!empty($ua)) {
            $fingerprint = md5($ua);
        }
    }

    if (!empty($fingerprint)) {
        db_query(
            'UPDATE ?:is2or_login_sessions SET is_active = 0, logged_out_at = ?i WHERE user_id = ?i AND device_fingerprint = ?s AND is_active = 1',
            TIME,
            $user_id,
            $fingerprint
        );
    }
}

/**
 * Hook: dispatch_before_send_response
 * Keeps `last_active_at` and `session_id` fresh for the current device,
 * and handles the logout_session / logout_all POST actions.
 */
function fn_is2or_login_activity_dispatch_before_send_response($controller, $mode, $action, $dispatch_extra, $area)
{
    $auth = Tygh::$app['session']['auth'] ?? [];
    $user_id = (int) ($auth['user_id'] ?? 0);

    if ($user_id > 0) {
        if (fn_is2or_login_activity_check_force_logout($user_id)) {
            return;
        }
        fn_is2or_login_activity_touch_current_session($user_id);
    }
}

/**
 * Cek apakah row tracking untuk device/session saat ini sudah dinonaktifkan
 * (is_active = 0) akibat "Logout" atau "Logout all" dari device lain.
 * Jika ya, hancurkan auth/session PHP saat ini lewat fn_logout() bawaan
 * CS-Cart — ini bekerja di backend session apa pun (Database/Redis/
 * Memcached) karena memanggil API session resmi, bukan query manual.
 *
 * @param int $user_id
 * @return bool True jika session saat ini baru saja dipaksa logout.
 */
function fn_is2or_login_activity_check_force_logout($user_id)
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($ua)) {
        return false;
    }
    $fingerprint = md5($ua);

    $row = db_get_row(
        'SELECT is_active FROM ?:is2or_login_sessions WHERE user_id = ?i AND device_fingerprint = ?s',
        $user_id,
        $fingerprint
    );

    if (empty($row)) {
        return false;
    }

    if ((int) $row['is_active'] === 1) {
        return false;
    }

    $auth = &Tygh::$app['session']['auth'];
    fn_user_logout($auth);

    return true;
}

/**
 * Updates last_active_at (and session_id, in case it was regenerated)
 * for the row matching the current browser/device. Throttled to avoid
 * writing on every single request.
 */
function fn_is2or_login_activity_touch_current_session($user_id)
{
    static $touched = false;
    if ($touched) {
        return;
    }
    $touched = true;

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($ua)) {
        return;
    }
    $fingerprint = md5($ua);
    $session_id = Tygh::$app['session']->getID();

    // Throttle: only write if the last update was more than 60 seconds ago,
    // to avoid hammering the DB on every page load.
    $row = db_get_row(
        'SELECT id, last_active_at FROM ?:is2or_login_sessions WHERE user_id = ?i AND device_fingerprint = ?s',
        $user_id,
        $fingerprint
    );

    if (empty($row)) {
        // No row yet (e.g. addon was just installed for an already-logged-in
        // user, so sucess_user_login never fired for this session).
        // Create one now so the list isn't empty until next login.
        $device = fn_is2or_login_activity_parse_ua($ua);
        db_query(
            'REPLACE INTO ?:is2or_login_sessions ?e',
            [
                'user_id'            => $user_id,
                'session_id'         => $session_id,
                'device_fingerprint' => $fingerprint,
                'user_agent'         => substr($ua, 0, 255),
                'device_type'        => $device['type'],
                'device_name'        => $device['name'],
                'browser'            => $device['browser'],
                'os'                 => $device['os'],
                'ip_address'         => fn_is2or_login_activity_get_ip(),
                'login_at'           => TIME,
                'last_active_at'     => TIME,
                'logged_out_at'      => null,
                'is_active'          => 1,
            ]
        );
        return;
    }

    if ((TIME - (int) $row['last_active_at']) < 60) {
        return;
    }

    db_query(
        'UPDATE ?:is2or_login_sessions SET session_id = ?s, last_active_at = ?i, is_active = 1 WHERE id = ?i',
        $session_id,
        TIME,
        $row['id']
    );
}

/**
 * Returns all active devices/sessions for a user, current device first.
 *
 * @param int $user_id
 * @return array
 */
function fn_is2or_login_activity_get_sessions($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return [];
    }

    $rows = db_get_array(
        'SELECT * FROM ?:is2or_login_sessions WHERE user_id = ?i AND is_active = 1 ORDER BY last_active_at DESC',
        $user_id
    );

    if (empty($rows)) {
        return [];
    }

    $current_session_id = Tygh::$app['session']->getID();
    $sessions = [];

    foreach ($rows as $row) {
        $sessions[] = [
            'id'          => (int) $row['id'],
            'session_id'  => $row['session_id'],
            'is_current'  => ($row['session_id'] === $current_session_id),
            'device_type' => $row['device_type'],
            'device_name' => $row['device_name'],
            'browser'     => $row['browser'],
            'os'          => $row['os'],
            'ip_address'  => $row['ip_address'],
            'user_agent'  => $row['user_agent'],
            'login_at'    => (int) $row['login_at'],
            'last_active' => (int) $row['last_active_at'],
        ];
    }

    usort($sessions, function ($a, $b) {
        if ($a['is_current'] !== $b['is_current']) {
            return $a['is_current'] ? -1 : 1;
        }
        return $b['last_active'] - $a['last_active'];
    });

    return $sessions;
}

/**
 * Marks one device/session as logged out (removes it from the list).
 * The current device can't be removed this way.
 *
 * Note: this only removes the entry from OUR tracking table — it does not
 * forcibly invalidate the underlying PHP session if the server uses a
 * non-Database session backend (Redis/Memcached). On Database backend it
 * additionally deletes the matching row from ?:sessions, which does force
 * an immediate logout.
 *
 * @param int $row_id  Row id in is2or_login_sessions (NOT the raw session_id).
 * @param int $user_id Safety check — only touch rows belonging to this user.
 * @return bool
 */
function fn_is2or_login_activity_logout_session($row_id, $user_id)
{
    $row_id = (int) $row_id;
    $user_id = (int) $user_id;

    if ($row_id <= 0 || $user_id <= 0) {
        return false;
    }

    $row = db_get_row(
        'SELECT id, session_id FROM ?:is2or_login_sessions WHERE id = ?i AND user_id = ?i',
        $row_id,
        $user_id
    );

    if (empty($row)) {
        return false;
    }

    if ($row['session_id'] === Tygh::$app['session']->getID()) {
        return false;
    }

    db_query('UPDATE ?:is2or_login_sessions SET is_active = 0, logged_out_at = ?i WHERE id = ?i', TIME, $row['id']);

    if (!empty($row['session_id'])) {
        db_query('DELETE FROM ?:sessions WHERE session_id = ?s', $row['session_id']);
    }

    return true;
}

/**
 * Logs out from ALL tracked devices except the current one.
 *
 * @param int $user_id
 * @return int Number of devices logged out.
 */
function fn_is2or_login_activity_logout_all_sessions($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return 0;
    }

    $current_session_id = Tygh::$app['session']->getID();

    $rows = db_get_array(
        'SELECT id, session_id FROM ?:is2or_login_sessions WHERE user_id = ?i AND is_active = 1',
        $user_id
    );

    $count = 0;
    foreach ($rows as $row) {
        if ($row['session_id'] === $current_session_id) {
            continue;
        }

        db_query('UPDATE ?:is2or_login_sessions SET is_active = 0, logged_out_at = ?i WHERE id = ?i', TIME, $row['id']);

        if (!empty($row['session_id'])) {
            db_query('DELETE FROM ?:sessions WHERE session_id = ?s', $row['session_id']);
        }

        $count++;
    }

    return $count;
}

/**
 * Parse a User-Agent string into type / browser / os / display name.
 *
 * @param string $ua
 * @return array{type: string, browser: string, os: string, name: string}
 */
function fn_is2or_login_activity_parse_ua($ua)
{
    if (empty($ua)) {
        $unknown = __('is2or_login_activity.unknown_device');
        return ['type' => 'unknown', 'browser' => $unknown, 'os' => $unknown, 'name' => $unknown];
    }

    // --- Detect device type ---
    $type = 'desktop';
    if (preg_match('/tablet|ipad/i', $ua)) {
        $type = 'tablet';
    } elseif (preg_match('/mobile|android|iphone|ipod|windows phone|blackberry|opera mini/i', $ua)) {
        $type = 'mobile';
    }

    // --- Detect OS ---
    $os = __('is2or_login_activity.unknown_os');
    if (preg_match('/windows nt 10/i', $ua))       { $os = 'Windows 10'; }
    elseif (preg_match('/windows nt 11/i', $ua))   { $os = 'Windows 11'; }
    elseif (preg_match('/windows nt 6\.3/i', $ua)) { $os = 'Windows 8.1'; }
    elseif (preg_match('/windows nt 6\.2/i', $ua)) { $os = 'Windows 8'; }
    elseif (preg_match('/windows nt 6\.1/i', $ua)) { $os = 'Windows 7'; }
    elseif (preg_match('/windows/i', $ua))          { $os = 'Windows'; }
    elseif (preg_match('/iphone os ([\d_]+)/i', $ua, $m)) {
        $os = 'iOS ' . str_replace('_', '.', $m[1]);
    } elseif (preg_match('/ipad.*os ([\d_]+)/i', $ua, $m)) {
        $os = 'iPadOS ' . str_replace('_', '.', $m[1]);
    } elseif (preg_match('/mac os x ([\d_]+)/i', $ua, $m)) {
        $os = 'macOS ' . str_replace('_', '.', $m[1]);
    } elseif (preg_match('/android ([\d.]+)/i', $ua, $m)) {
        $os = 'Android ' . $m[1];
    } elseif (preg_match('/linux/i', $ua)) {
        $os = 'Linux';
    } elseif (preg_match('/cros/i', $ua)) {
        $os = 'ChromeOS';
    }

    // --- Detect browser ---
    $browser = __('is2or_login_activity.unknown_browser');
    if (preg_match('/edg\/([\d.]+)/i', $ua, $m))        { $browser = 'Edge'; }
    elseif (preg_match('/opr\/([\d.]+)/i', $ua, $m))    { $browser = 'Opera'; }
    elseif (preg_match('/samsung/i', $ua))               { $browser = 'Samsung Browser'; }
    elseif (preg_match('/chrome\/([\d.]+)/i', $ua, $m)) { $browser = 'Chrome'; }
    elseif (preg_match('/firefox\/([\d.]+)/i', $ua, $m)){ $browser = 'Firefox'; }
    elseif (preg_match('/safari\/([\d.]+)/i', $ua, $m)) { $browser = 'Safari'; }
    elseif (preg_match('/msie|trident/i', $ua))          { $browser = 'Internet Explorer'; }

    $name = $browser . ' ' . __('is2or_login_activity.on') . ' ' . $os;

    return ['type' => $type, 'browser' => $browser, 'os' => $os, 'name' => $name];
}

/**
 * Best-effort real client IP (handles common reverse-proxy headers).
 */
function fn_is2or_login_activity_get_ip()
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '';
}

/**
 * Sanitize a numeric row id coming from POST.
 */
function fn_is2or_login_activity_sanitize_id($id)
{
    return (int) $id;
}

/**
 * Block content getter — called by the block manager.
 */
function fn_is2or_login_activity_get_block_data($value = '', array $block = [], array $block_scheme = [])
{
    $auth = Tygh::$app['session']['auth'] ?? [];
    $user_id = (int) ($auth['user_id'] ?? 0);

    if ($user_id <= 0) {
        return ['sessions' => [], 'user_id' => 0];
    }

    return [
        'sessions' => fn_is2or_login_activity_get_sessions($user_id),
        'user_id'  => $user_id,
    ];
}