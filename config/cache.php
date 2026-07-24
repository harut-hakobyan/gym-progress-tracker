<?php

use Illuminate\Support\Str;

return [
    'default' => env('CACHE_STORE', 'database'),

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => env('DB_CACHE_CONNECTION'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
        ],
    ],

    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel'), '_').'_cache'),
];
