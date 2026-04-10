<?php
/**
 * Quick diagnostic: tests if your hosting allows outbound cURL to Render.
 * Visit this page on your LIVE site, then DELETE it after checking.
 * ⚠️ DELETE THIS FILE AFTER TESTING — do not leave on production!
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== StockSathi Chatbot API Diagnostic ===\n\n";

$testUrl = 'https://chat-bot-09xg.onrender.com/api/chat/landing';

// 1. cURL availability
echo "1. cURL available: " . (function_exists('curl_init') ? "YES ✓" : "NO ✗") . "\n";

// 2. cURL version
if (function_exists('curl_version')) {
    $cv = curl_version();
    echo "   cURL version: " . $cv['version'] . "\n";
    echo "   SSL version: " . $cv['ssl_version'] . "\n";
}

// 3. DNS resolution test
echo "\n2. DNS resolve chat-bot-09xg.onrender.com: ";
$ip = @gethostbyname('chat-bot-09xg.onrender.com');
if ($ip === 'chat-bot-09xg.onrender.com') {
    echo "FAILED ✗ (DNS blocked or host unreachable)\n";
} else {
    echo "OK ✓ (resolved to $ip)\n";
}

// 4. Actual cURL test
echo "\n3. cURL POST to Render API: ";
if (function_exists('curl_init')) {
    $payload = json_encode(['message' => 'ping', 'history' => []]);
    $ch = curl_init($testUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp    = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err     = curl_error($ch);
    curl_close($ch);

    if ($resp === false || $err !== '') {
        echo "FAILED ✗\n   Error: $err\n";
        echo "\n⚠️  PROBLEM: Your hosting blocks outbound cURL to external APIs.\n";
        echo "   SOLUTION: Use Option 2 — direct browser JS call (see below).\n";
    } else {
        echo "SUCCESS ✓ (HTTP $code)\n";
        echo "   Response: " . substr($resp, 0, 200) . "\n";
        echo "\n✅ cURL works! The chatbot proxy should work. Check other issues.\n";
    }
} else {
    echo "SKIPPED (cURL not available)\n";
    echo "\n⚠️  PROBLEM: cURL is not installed on this server.\n";
}

// 5. file_get_contents fallback test
echo "\n4. file_get_contents outbound: ";
$testFgc = @file_get_contents('https://api.ipify.org');
if ($testFgc === false) {
    echo "BLOCKED ✗ (allow_url_fopen is off or outbound blocked)\n";
} else {
    echo "OK ✓ (server IP: $testFgc)\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
echo "⚠️  Remember to DELETE this file after testing!\n";
