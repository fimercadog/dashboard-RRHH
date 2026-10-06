<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost:3000',
        'https://dfctalentohumano.fidelmercadotech.com',
        'https://solucionesrrhh.fidelmercadotech.com',
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.ngrok-free\.(dev|app)$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Auth por token Bearer en el header Authorization, no cookies.
    'supports_credentials' => false,
];
