<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    // Orígenes permitidos para nuestra PWA en desarrollo local (Vite usa 5173 por defecto)
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:4173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // En false porque autenticamos con Bearer Token en el header Authorization, no con cookies
    'supports_credentials' => false,
];