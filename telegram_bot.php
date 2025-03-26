<?php
// Telegram Bot Main Script

// Include original functions
require_once 'function/function.php';
require_once 'function/settings.php';

// Load configuration
$config = require 'config.php';

// Telegram Bot Webhook Handler
function handleTelegramUpdate($update) {
    // Parse incoming update
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'];

    // Check if message is a credit card list
    if (preg_match('/^(\d{16}\|\d{2}\|\d{4}\|\d{3}(\n|$))+/', $text)) {
        // Save list to file
        file_put_contents('list.txt', $text);

        // Capture output of CC checker
        ob_start();
        require 'cli.php';
        $output = ob_get_clean();

        // Prepare results
        $results = [
            'Live' => file_exists('result/live.txt') ? file_get_contents('result/live.txt') : 'No live cards',
            'CVV' => file_exists('result/cvv.txt') ? file_get_contents('result/cvv.txt') : 'No CVV cards',
            'CCN' => file_exists('result/ccn.txt') ? file_get_contents('result/ccn.txt') : 'No CCN cards',
            'Dead' => file_exists('result/dead.txt') ? file_get_contents('result/dead.txt') : 'No dead cards'
        ];

        // Send results back to Telegram
        sendTelegramMessage($chatId, "CC Checker Results:\n" .
            "Live Cards: " . substr_count($results['Live'], "\n") . "\n" .
            "CVV Cards: " . substr_count($results['CVV'], "\n") . "\n" .
            "CCN Cards: " . substr_count($results['CCN'], "\n") . "\n" .
            "Dead Cards: " . substr_count($results['Dead'], "\n")
        );
    } else {
        sendTelegramMessage($chatId, "Please send credit card list in format:\nCARDNO|MONTH|YEAR|CVV");
    }
}

function sendTelegramMessage($chatId, $message) {
    global $config;
    $botToken = $config['telegram_bot_token'];
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $message
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}

// Webhook handler
$update = json_decode(file_get_contents('php://input'), true);
handleTelegramUpdate($update);
