<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Domain Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the domain(s) for your application. This is used for SSL
    | certificate generation and Nginx configuration.
    |
    */
    'domain' => env('LARADOX_DOMAIN', 'laravel.docker.localhost'),

    /*
    |--------------------------------------------------------------------------
    | Additional Domains
    |--------------------------------------------------------------------------
    |
    | You can specify additional domains for the SSL certificate.
    | These will be included when generating the mkcert certificate.
    |
    */
    'additional_domains' => [
        '*.docker.localhost',
        'docker.localhost',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Specify the default environment for Docker Compose.
    | Options: 'development', 'production'
    |
    */
    'environment' => env('LARADOX_ENV', 'development'),

    /*
    |--------------------------------------------------------------------------
    | Ports Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the ports exposed by the containers.
    |
    */
    'ports' => [
        'http' => env('LARADOX_HTTP_PORT', 80),
        'https' => env('LARADOX_HTTPS_PORT', 443),
        'frankenphp' => env('LARADOX_FRANKENPHP_PORT', 8080),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Workers
    |--------------------------------------------------------------------------
    |
    | Number of queue worker processes in production.
    |
    */
    'queue_workers' => env('LARADOX_QUEUE_WORKERS', 2),

    /*
    |--------------------------------------------------------------------------
    | PHP Configuration
    |--------------------------------------------------------------------------
    |
    | PHP version and user configuration.
    |
    */
    'php' => [
        'version' => '8.4',
        'user_id' => env('LARADOX_USER_ID', 1000),
        'group_id' => env('LARADOX_GROUP_ID', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL Configuration
    |--------------------------------------------------------------------------
    |
    | Configure SSL certificate paths.
    |
    */
    'ssl' => [
        'cert_path' => base_path('docker/nginx/ssl/cert.pem'),
        'key_path' => base_path('docker/nginx/ssl/key.pem'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Install Dependencies
    |--------------------------------------------------------------------------
    |
    | Automatically run composer install and npm install after setup.
    |
    */
    'auto_install' => true,
];
