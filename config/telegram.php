<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    'webhook_rate_limit_per_minute' => (int) env('TELEGRAM_WEBHOOK_RATE_LIMIT_PER_MINUTE', 120),
    'request_timeout' => (int) env('TELEGRAM_REQUEST_TIMEOUT', 10),
    'state_ttl_minutes' => (int) env('TELEGRAM_STATE_TTL_MINUTES', 120),
];
