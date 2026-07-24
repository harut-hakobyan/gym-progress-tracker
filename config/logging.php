<?php

use Monolog\Handler\NullHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
    ],
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => [env('LOG_STACK', 'single')],
            'ignore_exceptions' => false,
        ],
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],
        'telegram' => [
            'driver' => 'single',
            'path' => storage_path('logs/telegram.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],
        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],
    ],
];
