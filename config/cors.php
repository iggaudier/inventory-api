<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // This API authenticates with an Authorization bearer token, not a
    // cross-site session cookie. Keep this list explicit: CORS must never
    // reflect arbitrary origins in production.
    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000'))
    )),
    'allowed_origins_patterns' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGIN_PATTERNS', ''))
    )),
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
