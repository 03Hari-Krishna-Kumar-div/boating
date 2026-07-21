<?php
/**
 * Debug login flow
 */

define('BASE_URL', 'http://127.0.0.1:8002');

echo "=== Debug Login Flow ===\n\n";

// Step 1: GET login page
echo "Step 1: GET /login\n";
$ch = curl_init(BASE_URL . '/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_COOKIEJAR => __DIR__ . '/debug_cookies.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/debug_cookies.txt',
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($resp, $headerSize);
$headers = substr($resp, 0, $headerSize);
curl_close($ch);

echo "HTTP $httpCode\n";
echo "--- Response Headers ---\n$headers\n";
echo "--- Cookies in jar ---\n";
echo file_get_contents(__DIR__ . '/debug_cookies.txt') . "\n";

// Extract CSRF token
preg_match('/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/', $body, $m);
$token = $m[1] ?? 'NOT FOUND';
echo "CSRF Token: $token\n\n";

// Step 2: POST login
echo "Step 2: POST /login\n";
$ch = curl_init(BASE_URL . '/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $token,
        'email' => 'admin@brms.local',
        'password' => 'password',
    ]),
    CURLOPT_COOKIEJAR => __DIR__ . '/debug_cookies.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/debug_cookies.txt',
    CURLOPT_FOLLOWLOCATION => false,
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($resp, $headerSize);
$headers = substr($resp, 0, $headerSize);
curl_close($ch);

echo "HTTP $httpCode\n";
echo "--- Response Headers ---\n$headers\n";
echo "--- Body (first 500 chars) ---\n" . substr($body, 0, 500) . "\n\n";
echo "--- Cookies in jar ---\n";
echo file_get_contents(__DIR__ . '/debug_cookies.txt') . "\n";

// Step 3: Check if authenticated - GET /dashboard
echo "Step 3: GET /dashboard\n";
$ch = curl_init(BASE_URL . '/dashboard');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_COOKIEJAR => __DIR__ . '/debug_cookies.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/debug_cookies.txt',
    CURLOPT_FOLLOWLOCATION => false,
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($resp, $headerSize);
curl_close($ch);

echo "HTTP $httpCode\n";
if ($httpCode === 200) {
    echo "✓ Authenticated! Dashboard loaded.\n";
} elseif ($httpCode === 302) {
    echo "✗ Redirected (not authenticated)\n";
    // Get redirect location
    preg_match('/Location: (.+)/i', $headers, $loc);
    echo "  Redirect to: " . ($loc[1] ?? 'unknown') . "\n";
} else {
    echo "Unexpected HTTP: $httpCode\n";
}

// Step 4: Try API
echo "\nStep 4: GET /api/dashboard\n";
$ch = curl_init(BASE_URL . '/api/dashboard');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_COOKIEJAR => __DIR__ . '/debug_cookies.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/debug_cookies.txt',
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$body = substr($resp, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
curl_close($ch);

echo "HTTP $httpCode\n";
echo "Body: $body\n\n";

// Cleanup
@unlink(__DIR__ . '/debug_cookies.txt');
