<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Cross-Origin Resource Sharing (CORS) allows requests from other origins
    | to access this API. This configuration defines which origins, methods,
    | and headers are allowed.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Define which domains are allowed to make CORS requests to this API.
    | Use '*' to allow any origin (NOT RECOMMENDED for production).
    | Use specific domains for production security.
    |
    | Examples:
    |   - 'https://mobile-app.com'
    |   - 'https://admin.domain.com'
    |   - 'http://localhost:3000'
    |
    */
    'allowed_origins' => [
        'http://localhost:3000',           // Local React dev server
        'http://localhost:8000',           // Local Laravel dev server
        'http://localhost:8081',           // Local React Native Metro bundler
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8081',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allow Wildcard Origins
    |--------------------------------------------------------------------------
    |
    | Enable or disable wildcard (*) origin matching.
    | When true, allows requests from any origin.
    | IMPORTANT: Only enable for development environments.
    |
    */
    'allow_all_origins' => env('CORS_ALLOW_ALL_ORIGINS', false),

    /*
    |--------------------------------------------------------------------------
    | Allowed HTTP Methods
    |--------------------------------------------------------------------------
    |
    | Specify which HTTP methods are allowed in CORS requests.
    |
    */
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed HTTP Headers
    |--------------------------------------------------------------------------
    |
    | Specify which request headers are allowed in CORS requests.
    | Always include Content-Type and Authorization for API requests.
    |
    */
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Accept-Language',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exposed HTTP Headers
    |--------------------------------------------------------------------------
    |
    | Specify which response headers should be accessible to the client.
    |
    */
    'exposed_headers' => [
        'Content-Length',
        'X-Total-Count',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Age (Cache Control)
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) the browser can cache CORS preflight responses.
    | Reduces preflight requests for the same endpoint.
    | Set to -1 to disable caching.
    |
    */
    'max_age' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Whether the API supports credentials (cookies, authorization headers).
    | Set to true if requests need to send authentication tokens.
    |
    */
    'supports_credentials' => true,
];
