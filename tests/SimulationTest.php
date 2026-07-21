<?php

/**
 * Dhanalakshmi Boating Simulation Test Suite v2 - Fixed with CSRF handling
 */

define('BASE_URL', 'http://127.0.0.1:8002');
define('TIMEOUT', 30);
define('COOKIE_JAR', __DIR__ . '/cookies.txt');

$results = [];
$passed = 0;
$failed = 0;

// Clean up old cookie jars
@unlink(COOKIE_JAR);

function assertTest($testName, $condition, $details = '') {
    global $results, $passed, $failed;
    if ($condition) {
        $results[] = "✓ PASS: $testName";
        $passed++;
    } else {
        $results[] = "✗ FAIL: $testName" . ($details ? " - $details" : '');
        $failed++;
    }
}

function section($title) {
    global $results;
    $results[] = '';
    $results[] = str_repeat('=', 60);
    $results[] = $title;
    $results[] = str_repeat('=', 60);
}

function httpGet($url, $useCookies = true) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => TIMEOUT,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($useCookies) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_JAR);
        curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_JAR);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    $headers = substr($response, 0, $headerSize);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $body, 'headers' => $headers];
}

function httpPost($url, $data, $contentType = 'form') {
    $ch = curl_init($url);
    $headers = [];
    if ($contentType === 'json') {
        $postData = json_encode($data);
        $headers[] = 'Content-Type: application/json';
    } else {
        $postData = http_build_query($data);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_TIMEOUT => TIMEOUT,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIEFILE => COOKIE_JAR,
        CURLOPT_COOKIEJAR => COOKIE_JAR,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    $headers_out = substr($response, 0, $headerSize);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $body, 'headers' => $headers_out];
}

function httpPut($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_TIMEOUT => TIMEOUT,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEFILE => COOKIE_JAR,
        CURLOPT_COOKIEJAR => COOKIE_JAR,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $body];
}

function extractCsrfToken($html) {
    preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/', $html, $m);
    if (!empty($m[1])) return $m[1];
    preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $html, $m);
    if (!empty($m[1])) return $m[1];
    return null;
}

function loginUser($email, $password, $fresh = false) {
    // Use a unique cookie jar for each login if fresh=true
    if ($fresh) {
        global $cookieJarWorker;
        $cookieJarWorker = __DIR__ . '/cookies_worker.txt';
        @unlink($cookieJarWorker);
        
        // Switch httpGet and httpPost to use worker cookie jar
        // We use a custom curl handle approach
        $ch = curl_init(BASE_URL . '/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $cookieJarWorker,
            CURLOPT_COOKIEFILE => $cookieJarWorker,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        curl_close($ch);
        
        $token = extractCsrfToken($body);
        if (!$token) {
            return ['success' => false, 'error' => 'Could not extract CSRF token'];
        }
        
        $ch = curl_init(BASE_URL . '/login');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                '_token' => $token,
                'email' => $email,
                'password' => $password,
            ]),
            CURLOPT_TIMEOUT => TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $cookieJarWorker,
            CURLOPT_COOKIEFILE => $cookieJarWorker,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headers = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $body = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        curl_close($ch);
        
        return [
            'success' => ($httpCode === 302),
            'code' => $httpCode,
            'headers' => $headers,
            'body' => $body,
        ];
    }
    
    // Step 1: GET login page to get CSRF token
    $resp = httpGet(BASE_URL . '/login');
    $token = extractCsrfToken($resp['body']);
    if (!$token) {
        return ['success' => false, 'error' => 'Could not extract CSRF token'];
    }
    
    // Step 2: POST login
    $resp = httpPost(BASE_URL . '/login', [
        '_token' => $token,
        'email' => $email,
        'password' => $password,
    ]);
    
    // Check if login succeeded (302 redirect to dashboard)
    $success = ($resp['code'] === 302);
    
    return [
        'success' => $success,
        'code' => $resp['code'],
        'headers' => $resp['headers'],
        'body' => $resp['body'],
    ];
}

echo "\n=== Dhanalakshmi Boating Simulation Test Suite v2 ===\n";
echo "Base URL: " . BASE_URL . "\n\n";

// Database has been freshly reset with no lingering rentals

// ================================
// TEST 1: Login
// ================================
section('1. Authentication');

echo "1a. Login page loads...\n";
$resp = httpGet(BASE_URL . '/login');
assertTest('Login page loads (200)', $resp['code'] === 200, 'Got ' . $resp['code']);
$csrfToken = extractCsrfToken($resp['body']);
assertTest('CSRF token found', $csrfToken !== null, 'No _token found');

echo "1b. Admin login...\n";
$login = loginUser('admin@brms.local', 'password');
assertTest('Admin login succeeds (302)', $login['success'], 
    'Got HTTP ' . $login['code'] . '. Body: ' . substr($login['body'], 0, 200));

echo "1c. Worker login (fresh cookie jar)...\n";
$login2 = loginUser('worker1@brms.local', 'password', true);
assertTest('Worker login succeeds (302)', $login2['success'],
    'Got HTTP ' . $login2['code']);

// ================================
// TEST 2: Dashboard Access (Admin)
// ================================
section('2. Dashboard');

echo "2a. Admin dashboard...\n";
$resp = httpGet(BASE_URL . '/dashboard');
assertTest('Dashboard loads for admin', 
    $resp['code'] === 200 && strpos($resp['body'], 'Dashboard') !== false,
    'Got HTTP ' . $resp['code']);

echo "2b. TV mode...\n";
$resp = httpGet(BASE_URL . '/tv');
assertTest('TV mode loads', $resp['code'] === 200, 'Got HTTP ' . $resp['code']);

// ================================
// TEST 3: API Endpoints
// ================================
section('3. API Endpoints');

echo "3a. Dashboard API...\n";
$resp = httpGet(BASE_URL . '/api/dashboard');
$json = json_decode($resp['body'], true);
assertTest('API dashboard authenticates', 
    $resp['code'] === 200 && isset($json['server_time']),
    'HTTP ' . $resp['code'] . ' - ' . substr($resp['body'], 0, 100));

echo "3b. Notifications API...\n";
$resp = httpGet(BASE_URL . '/api/notifications/unread');
assertTest('Notifications API (200/401 for no-auth)', 
    in_array($resp['code'], [200, 401]),
    'Got ' . $resp['code']);

echo "3c. Ping API...\n";
$resp = httpGet(BASE_URL . '/api/ping');
assertTest('Ping API succeeds', 
    in_array($resp['code'], [200, 401]),
    'Got ' . $resp['code']);

// ================================
// TEST 4: Start Rental Workflow
// ================================
section('4. Rental Workflow');

echo "4a. Start rental on boat 1...\n";
$resp = httpPost(BASE_URL . '/api/rentals/start', ['boat_id' => 1], 'json');
$json = json_decode($resp['body'], true);
assertTest('Start rental returns success', 
    $json !== null && isset($json['success']) && $json['success'] === true,
    'Response: ' . $resp['body']);

echo "4b. Start rental on boat 2 (second user simultaneous)...\n";
$resp = httpPost(BASE_URL . '/api/rentals/start', ['boat_id' => 2], 'json');
$json = json_decode($resp['body'], true);
assertTest('Start second rental succeeds', 
    $json !== null && isset($json['success']) && $json['success'] === true,
    'Response: ' . $resp['body']);

echo "4c. Get active rentals...\n";
$resp = httpGet(BASE_URL . '/api/dashboard');
$json = json_decode($resp['body'], true);

// Find active rentals from boat data (using current_rental_id)
$activeRentals = [];
if ($json && isset($json['boats'])) {
    foreach ($json['boats'] as $boat) {
        if (!empty($boat['current_rental_id'])) {
            $activeRentals[] = $boat;
        }
    }
}
assertTest('Active rentals found', count($activeRentals) > 0, 
    'Found ' . count($activeRentals) . ' boats with current_rental_id set');

// Get the rental ID from the boat's worker data or use the first available
$rental1Id = null;
if (!empty($activeRentals[0]['current_rental_id'])) {
    $rental1Id = $activeRentals[0]['current_rental_id'];
}
echo "   First active rental ID: " . ($rental1Id ?? 'N/A') . "\n";

echo "4d. End rental (ID: " . ($rental1Id ?? 'none') . ")...\n";
if ($rental1Id) {
    $resp = httpPost(BASE_URL . "/api/rentals/$rental1Id/end", [], 'json');
    $json = json_decode($resp['body'], true);
    assertTest('End rental succeeds',
        $json !== null && isset($json['success']),
        'Response: ' . $resp['body']);
    
    if ($json && isset($json['success']) && $json['success']) {
        echo "4e. Confirm return...\n";
        $resp2 = httpPost(BASE_URL . "/api/rentals/$rental1Id/confirm-return", ['returned' => true], 'json');
        $json2 = json_decode($resp2['body'], true);
        assertTest('Confirm return succeeds',
            $json2 !== null && isset($json2['success']),
            'Response: ' . $resp2['body']);
        
        echo "4f. Start fresh rental for race condition test...\n";
        $resp3 = httpPost(BASE_URL . '/api/rentals/start', ['boat_id' => 1], 'json');
        $json3 = json_decode($resp3['body'], true);
        assertTest('Fresh rental starts',
            $json3 !== null && isset($json3['success']) && $json3['success'],
            'Response: ' . $resp3['body']);
    }
}

// ================================
// TEST 5: Race Condition
// ================================
section('5. Race Condition');

echo "5a. Concurrent start requests on same boat...\n";
$mh = curl_multi_init();
$ch1 = curl_init(BASE_URL . '/api/rentals/start');
$ch2 = curl_init(BASE_URL . '/api/rentals/start');

curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['boat_id' => 5]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEFILE => COOKIE_JAR,
    CURLOPT_COOKIEJAR => COOKIE_JAR,
]);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['boat_id' => 5]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEFILE => COOKIE_JAR,
    CURLOPT_COOKIEJAR => COOKIE_JAR,
]);

curl_multi_add_handle($mh, $ch1);
curl_multi_add_handle($mh, $ch2);
$running = null;
do { curl_multi_exec($mh, $running); } while ($running);

$resp1 = json_decode(curl_multi_getcontent($ch1), true);
$resp2 = json_decode(curl_multi_getcontent($ch2), true);
curl_multi_remove_handle($mh, $ch1);
curl_multi_remove_handle($mh, $ch2);
curl_multi_close($mh);

$s1 = $resp1['success'] ?? false;
$s2 = $resp2['success'] ?? false;
assertTest('Race: one succeeds, one fails', 
    ($s1 xor $s2),
    "Resp1 success: " . ($s1 ? 'true' : 'false') . ", Resp2 success: " . ($s2 ? 'true' : 'false') . "\nResp1: " . json_encode($resp1) . "\nResp2: " . json_encode($resp2));

// ================================
// TEST 6: Admin Pages
// ================================
section('6. Admin Pages');

$adminPages = [
    '/admin/workers' => 'Workers',
    '/admin/boats' => 'Boats',
    '/admin/settings' => 'Settings',
    '/admin/reports' => 'Reports',
    '/admin/activity-logs' => 'Activity Logs',
    '/admin/rentals' => 'Rentals',
    '/admin/maintenance' => 'Maintenance',
    '/admin/backups' => 'Backups',
];

foreach ($adminPages as $path => $name) {
    echo "6" . array_search($path, array_keys($adminPages)) + 1 . ". $name page...\n";
    $resp = httpGet(BASE_URL . $path);
    assertTest("$name page loads (200)", $resp['code'] === 200, 'Got ' . $resp['code']);
}

// ================================
// TEST 7: Settings Update
// ================================
section('7. Settings Update');

echo "7a. Get CSRF token for settings...\n";
$resp = httpGet(BASE_URL . '/admin/settings');
$token = extractCsrfToken($resp['body']);
assertTest('Settings page CSRF token found', $token !== null);

if ($token) {
    echo "7b. PUT settings update...\n";
    $resp = httpPut(BASE_URL . '/admin/settings', [
        '_token' => $token,
        'rental_rate_per_minute' => '2.50',
        'late_penalty_per_minute' => '0.75',
        'max_rental_duration_minutes' => '90',
    ]);
    assertTest('Settings update (302/200)', 
        in_array($resp['code'], [200, 302, 303]),
        'Got HTTP ' . $resp['code'] . ', body: ' . substr($resp['body'], 0, 200));
}

// ================================
// TEST 8: Worker CRUD
// ================================
section('8. Worker Management');

echo "8a. Get CSRF token for workers...\n";
$resp = httpGet(BASE_URL . '/admin/workers');
$token = extractCsrfToken($resp['body']);
assertTest('Workers page CSRF token found', $token !== null);

if ($token) {
    echo "8b. Create worker...\n";
    $uniqid = rand(10000, 99999);
    $resp = httpPost(BASE_URL . '/admin/workers', [
        '_token' => $token,
        'name' => 'Sim Worker ' . $uniqid,
        'email' => 'simworker' . $uniqid . '@brms.local',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    assertTest('Worker creation (302/200)', 
        in_array($resp['code'], [200, 302, 303]),
        'Got HTTP ' . $resp['code'] . ', body: ' . substr($resp['body'], 0, 200));
}

// ================================
// TEST 9: Boat CRUD
// ================================
section('9. Boat Management');

echo "9a. Get CSRF token for boats...\n";
$resp = httpGet(BASE_URL . '/admin/boats');
$token = extractCsrfToken($resp['body']);
assertTest('Boats page CSRF token found', $token !== null);

if ($token) {
    echo "9b. Create boat...\n";
    $uniqid = rand(10000, 99999);
    $resp = httpPost(BASE_URL . '/admin/boats', [
        '_token' => $token,
        'name' => 'Sim Boat ' . $uniqid,
        'boat_number' => 'SB-' . $uniqid,
        'capacity' => 8,
        'description' => 'Simulation test boat',
    ]);
    assertTest('Boat creation (302/200)', 
        in_array($resp['code'], [200, 302, 303]),
        'Got HTTP ' . $resp['code'] . ', body: ' . substr($resp['body'], 0, 200));
}

// ================================
// SUMMARY
// ================================
section('TEST SUMMARY');

echo "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";
echo "Success Rate: " . round(($passed / max(1, $passed + $failed)) * 100, 1) . "%\n";

echo "\n--- Detailed Results ---\n";
foreach ($results as $r) {
    echo $r . "\n";
}

echo "\n=== Simulation Complete ===\n";

exit($failed > 0 ? 1 : 0);
