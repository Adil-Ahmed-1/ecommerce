<?php
session_start();
include('config/db.php');

header('Content-Type: application/json');

// Clean old active visitors (3 min timeout)
mysqli_query($conn, "DELETE FROM active_visitors WHERE last_activity < NOW() - INTERVAL 1 Hours");

 $ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
 $session_id  = session_id();
 $page_url    = substr($_SERVER['REQUEST_URI'] ?? '/', 0, 500);
 $referrer    = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);
 $user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? '';

/* ===== BROWSER DETECTION ===== */
 $browser = 'Unknown'; $browser_version = '';
if (preg_match('/Firefox\/([\d.]+)/i', $user_agent, $m)) {
    $browser = 'Firefox'; $browser_version = $m[1];
} elseif (preg_match('/Edg\/([\d.]+)/i', $user_agent, $m)) {
    $browser = 'Edge'; $browser_version = $m[1];
} elseif (preg_match('/OPR\/([\d.]+)/i', $user_agent, $m)) {
    $browser = 'Opera'; $browser_version = $m[1];
} elseif (preg_match('/Chrome\/([\d.]+)/i', $user_agent, $m)) {
    $browser = 'Chrome'; $browser_version = $m[1];
} elseif (preg_match('/Safari\/([\d.]+)/i', $user_agent, $m) && !preg_match('/Chrome/i', $user_agent)) {
    $browser = 'Safari'; $browser_version = $m[1];
}

/* ===== OS DETECTION ===== */
 $os = 'Unknown';
if (preg_match('/Windows NT 10/i', $user_agent))       $os = 'Windows 11';
elseif (preg_match('/Windows NT 6\.3/i', $user_agent))  $os = 'Windows 8.1';
elseif (preg_match('/Windows NT 6\.1/i', $user_agent))  $os = 'Windows 7';
elseif (preg_match('/Windows/i', $user_agent))           $os = 'Windows';
elseif (preg_match('/Mac OS X ([\d_]+)/i', $user_agent, $m)) $os = 'macOS ' . str_replace('_', '.', $m[1]);
elseif (preg_match('/Android ([\d.]+)/i', $user_agent, $m))  $os = 'Android ' . $m[1];
elseif (preg_match('/iPhone OS ([\d_]+)/i', $user_agent, $m)) $os = 'iOS ' . str_replace('_', '.', $m[1]);
elseif (preg_match('/Linux/i', $user_agent))             $os = 'Linux';

/* ===== DEVICE TYPE ===== */
 $device_type = 'Desktop';
if (preg_match('/Mobile|Android(?!.*Tablet)|iPhone|iPod|BlackBerry|IEMobile/i', $user_agent)) {
    $device_type = 'Mobile';
} elseif (preg_match('/iPad|Android(.*Tablet)|Tablet|Kindle|Silk/i', $user_agent)) {
    $device_type = 'Tablet';
}

/* ===== UNIQUE CHECK (same IP, same day) ===== */
 $today = date('Y-m-d');
 $chk = mysqli_query($conn, "SELECT id FROM visitors WHERE ip_address = '" . mysqli_real_escape_string($conn, $ip) . "' AND visit_date = '$today' LIMIT 1");
 $is_unique = (mysqli_num_rows($chk) === 0) ? 1 : 0;

/* ===== GEOLOCATION (free ip-api.com, skip localhost) ===== */
 $country = 'Unknown'; $city = 'Unknown';
 $local_ips = ['127.0.0.1', '::1', '192.168.0.1', '10.0.0.1'];
if (!in_array($ip, $local_ips)) {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $geo = @file_get_contents("http://ip-api.com/json/" . $ip . "?fields=country,city,status", false, $ctx);
    if ($geo) {
        $gd = json_decode($geo, true);
        if ($gd && isset($gd['status']) && $gd['status'] === 'success') {
            $country = mysqli_real_escape_string($conn, $gd['country']);
            $city    = mysqli_real_escape_string($conn, $gd['city']);
        }
    }
}

/* ===== INSERT VISITOR LOG ===== */
 $ip_esc    = mysqli_real_escape_string($conn, $ip);
 $browser_e = mysqli_real_escape_string($conn, $browser);
 $bv_e      = mysqli_real_escape_string($conn, $browser_version);
 $os_e      = mysqli_real_escape_string($conn, $os);
 $dt_e      = mysqli_real_escape_string($conn, $device_type);
 $page_e    = mysqli_real_escape_string($conn, $page_url);
 $ref_e     = mysqli_real_escape_string($conn, $referrer);
 $sid_e     = mysqli_real_escape_string($conn, $session_id);

mysqli_query($conn, "INSERT INTO visitors (ip_address, browser, browser_version, os, device_type, page_url, referrer, country, city, session_id, visit_date, visit_time, is_unique) 
VALUES ('$ip_esc','$browser_e','$bv_e','$os_e','$dt_e','$page_e','$ref_e','$country','$city','$sid_e','$today',NOW(),$is_unique)");

/* ===== UPSERT ACTIVE VISITORS ===== */
mysqli_query($conn, "INSERT INTO active_visitors (session_id, ip_address, page_url, last_activity) 
VALUES ('$sid_e','$ip_esc','$page_e',NOW()) 
ON DUPLICATE KEY UPDATE page_url='$page_e', last_activity=NOW(), ip_address='$ip_esc'");

/* ===== RETURN ACTIVE COUNT ===== */
 $ar = mysqli_query($conn, "SELECT COUNT(*) as total FROM active_visitors");
 $ac = mysqli_fetch_assoc($ar);

echo json_encode([
    'success'      => true,
    'active_count' => (int)$ac['total']
]);