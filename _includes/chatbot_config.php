<?php
/**
 * StockSathi Chatbot – API URL configuration
 *
 * Change URL for local testing vs production:
 * 1) Edit CHATBOT_API_URL below, OR
 * 2) Create project-root chatbot_env.php (copy from chatbot_env.example.php)
 */
if (!defined('CHATBOT_API_URL')) {
    $envFile = dirname(__DIR__) . '/chatbot_env.php';
    if (is_file($envFile)) {
        $chatbotEnv = require $envFile;
        define('CHATBOT_API_URL', $chatbotEnv['api_url'] ?? 'https://chat-bot-09xg.onrender.com/api/chat');
    } else {
        // Fallback to Render deployment
        define('CHATBOT_API_URL', 'https://chat-bot-09xg.onrender.com/api/chat');
    }
}

if (!function_exists('stocksathi_chatbot_remote_origin')) {
    /**
     * Origin for Render (or custom) chat API — derived from CHATBOT_API_URL by stripping /api/chat…
     */
    function stocksathi_chatbot_remote_origin() {
        $u = defined('CHATBOT_API_URL') ? CHATBOT_API_URL : 'https://chat-bot-09xg.onrender.com/api/chat';
        $base = preg_replace('#/api/chat(?:/.*)?$#i', '', $u);
        $base = rtrim((string)$base, '/');
        return $base !== '' ? $base : 'https://chat-bot-09xg.onrender.com';
    }
}
