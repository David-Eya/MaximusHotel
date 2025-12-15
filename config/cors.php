<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000', 
        'http://127.0.0.1:3000', 
        'http://localhost', 
        'http://127.0.0.1',
        // GitHub Pages URL
        'https://david-eya.github.io',
        'https://david-eya.github.io/MaximusHotel',
        // Hostinger backend domain (for same-origin requests)
        'https://hotelmaximus.bytevortexz.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];


