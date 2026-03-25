<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control which cross-origin requests are permitted for
    | the PriceShare API. The React dev server runs on port 3000 and the
    | production build will be served from a specific domain – update the
    | allowed_origins list accordingly before deploying.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Must be false when using Bearer token auth (not cookie-based sessions).
    'supports_credentials' => false,

];

