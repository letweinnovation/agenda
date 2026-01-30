<?php

$config = [
    'trello_api_key' => 'YOUR_API_KEY',
    'trello_token' => 'YOUR_TOKEN',
    'trello_board_id' => 'YOUR_BOARD_ID',
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $localConfig = require __DIR__ . '/config.local.php';
    $config = array_merge($config, $localConfig);
}

return $config;
