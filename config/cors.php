<?php

return [
    'paths' => ['api/*', 'api/register', 'api/login', 'api/logout', 'api/user', 'sanctum/csrf-cookie', 'storage/*'],
    'allowed_methods' => ['*'],
'allowed_origins' => array_filter([
        'http://localhost:5173',
        'http://localhost:5073',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5073',
        env('FRONTEND_URL'),
    ]),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Content-Disposition', 'Content-Length', 'Content-Type'],
    'max_age' => 0,
    'supports_credentials' => true,
];
