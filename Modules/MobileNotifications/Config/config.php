<?php
return [
    'name' => 'MobileNotifications',
    'firebase_credentials_path' => env('MOBILENOTIFICATIONS_FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase_default.json')), // Renamed key and updated env variable
    'project_id' => env('MOBILENOTIFICATIONS_PROJECT_ID', ''),
    'default_title' => env('MOBILENOTIFICATIONS_DEFAULT_TITLE', 'FreeScout'),
    'server_name' => env('MOBILENOTIFICATIONS_SERVER_NAME', env('APP_NAME', 'FreeScout')),
    'enabled' => env('MOBILENOTIFICATIONS_ENABLED', true),
];
