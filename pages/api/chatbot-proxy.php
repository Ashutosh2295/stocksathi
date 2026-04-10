<?php
/**
 * StockSathi Chatbot Proxy – forwards to your Render chatbot server.
 * Handles the Render free-tier cold start (up to 60s wake time) gracefully.
 *
 * Endpoints used:
 *   POST https://chat-bot-09xg.onrender.com/api/chat/landing   (landing page)
 *   POST https://chat-bot-09xg.onrender.com/api/chat/internal  (logged-in users)
 */
@set_time_limit(120); // Extend PHP timeout for Render cold start
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../_includes/chatbot_config.php';
$chatbotRemoteOrigin = stocksathi_chatbot_remote_origin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$message     = isset($input['message'])  ? trim((string)$input['message'])  : '';
$contextType = isset($input['type'])     ? trim((string)$input['type'])     : 'internal';
$history     = isset($input['history'])  && is_array($input['history']) ? $input['history'] : [];

if ($message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty message']);
    exit;
}

// Route to correct endpoint based on context
$endpoint = ($contextType === 'landing') ? '/api/chat/landing' : '/api/chat/internal';
$url      = $chatbotRemoteOrigin . $endpoint;

$payload = json_encode(['message' => $message, 'history' => $history]);

$response = null;
$httpCode = 0;

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 115,     // 115s — covers Render cold start (~60s) within our 120s PHP limit
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        http_response_code(503);
        $hint = trim((string)$curlErr);
        $msg  = 'Could not connect to the AI server from this host.';
        if ($hint !== '') {
            $msg .= ' (' . $hint . ')';
        }
        $msg .= ' Render may be waking up — wait 30s and retry. If it never works, your host may block outbound HTTPS to external APIs (common on free hosting).';
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
} else {
    // Fallback: file_get_contents (if curl not available)
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content' => $payload,
            'timeout' => 90,
        ],
    ]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error'   => 'The AI assistant is starting up. Please wait 30 seconds and try again.',
        ]);
        exit;
    }
}

// Parse response
$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Invalid response from AI service. Try again.']);
    exit;
}

// Upstream HTTP not OK (4xx/5xx) — do not treat error bodies as assistant replies
if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code(502);
    $upErr = '';
    if (is_array($decoded)) {
        $upErr = $decoded['error'] ?? $decoded['detail'] ?? (is_string($decoded['message'] ?? null) ? $decoded['message'] : '');
    }
    if ($upErr === '') {
        $upErr = 'AI service returned HTTP ' . $httpCode . '.';
    }
    echo json_encode(['success' => false, 'error' => $upErr]);
    exit;
}

// Explicit failure payload while HTTP 200
if (is_array($decoded) && array_key_exists('success', $decoded) && $decoded['success'] === false) {
    http_response_code(502);
    $upErr = $decoded['error'] ?? $decoded['detail'] ?? 'AI request failed.';
    if (!is_string($upErr)) {
        $upErr = 'AI request failed.';
    }
    echo json_encode(['success' => false, 'error' => $upErr]);
    exit;
}

// Error field without a real reply (many APIs use { error: "..." } with 200 by mistake)
if (is_array($decoded) && !empty($decoded['error']) && empty($decoded['answer']) && empty($decoded['reply']) && empty($decoded['data']['answer'])) {
    http_response_code(502);
    $upErr = is_string($decoded['error']) ? $decoded['error'] : 'AI error.';
    echo json_encode(['success' => false, 'error' => $upErr]);
    exit;
}

// ── Normalize response format ─────────────────
// Your Render server may return { answer: "..." } or { reply: "..." } — handle both
$answer = '';
if (isset($decoded['answer'])) {
    $answer = $decoded['answer'];
} elseif (isset($decoded['reply'])) {
    $answer = $decoded['reply'];
} elseif (isset($decoded['data']['answer'])) {
    $answer = $decoded['data']['answer'];
} elseif (isset($decoded['message']) && is_string($decoded['message'])) {
    $answer = $decoded['message'];
} else {
    // Pass through as-is if already in correct format
    echo $response;
    exit;
}

echo json_encode([
    'success' => true,
    'data'    => [
        'answer'        => $answer,
        'quick_replies' => $decoded['quick_replies'] ?? ($decoded['data']['quick_replies'] ?? []),
        'action_type'   => $decoded['action_type']   ?? ($decoded['data']['action_type']   ?? null),
        'action_url'    => $decoded['action_url']    ?? ($decoded['data']['action_url']    ?? null),
    ],
]);
