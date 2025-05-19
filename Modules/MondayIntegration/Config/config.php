<?php

return [
    'name' => 'MondayIntegration',
    
    'api' => [
        'base_url' => 'https://api.monday.com/v2',
        'base_file_url' => 'https://api.monday.com/v2/file',
        'version' => '2024-01',
        'timeout' => env('MONDAY_API_TIMEOUT', 30),
        'retry_attempts' => env('MONDAY_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('MONDAY_RETRY_DELAY', 5),
    ],
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => env('MONDAY_RATE_LIMIT', 100),
        'window_minutes' => 1,
    ],
    'security' => [
        'encrypt_tokens' => true,
        'webhook_secret' => env('MONDAY_WEBHOOK_SECRET'),
    ],
    'sync' => [
        'batch_size' => env('MONDAY_SYNC_BATCH_SIZE', 50),
        'timeout' => env('MONDAY_SYNC_TIMEOUT', 300),
    ],
    'logging' => [
        'enabled' => true,
        'channel' => env('MONDAY_LOG_CHANNEL', 'daily'),
    ]
];
