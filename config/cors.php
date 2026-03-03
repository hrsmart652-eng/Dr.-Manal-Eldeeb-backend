<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

  
      'paths' => ['api/*'], // API routes only
 'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),
    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
    ],

    'exposed_headers' => [
        'Authorization',
        'Content-Type',
        'X-Requested-With',
    ],

    'max_age' => 3600,

 
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', false), // false by default, set to true if you need to support cookies or HTTP authentication

];

