<?php

use App\Services\Telegram\TelegramWebhookService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('telegram:webhook:set', function (): int {
    $service = app(TelegramWebhookService::class);
    $result = $service->setWebhook();

    $this->info($result ? 'Telegram webhook set.' : 'Failed to set Telegram webhook.');

    return $result ? 0 : 1;
});

Artisan::command('telegram:webhook:delete', function (): int {
    $service = app(TelegramWebhookService::class);
    $result = $service->deleteWebhook();

    $this->info($result ? 'Telegram webhook deleted.' : 'Failed to delete Telegram webhook.');

    return $result ? 0 : 1;
});

Artisan::command('telegram:webhook:info', function (): int {
    $service = app(TelegramWebhookService::class);
    $info = $service->getWebhookInfo();

    $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $info ? 0 : 1;
});
