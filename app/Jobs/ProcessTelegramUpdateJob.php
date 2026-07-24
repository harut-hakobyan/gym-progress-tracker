<?php

namespace App\Jobs;

use App\Services\Telegram\TelegramUpdateProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTelegramUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [5, 15, 30];

    public function __construct(public array $payload)
    {
    }

    public function handle(TelegramUpdateProcessor $processor): void
    {
        $processor->process($this->payload);
    }

    public function failed(Throwable $e): void
    {
        Log::channel('telegram')->error('telegram.job.failed', [
            'update_id' => data_get($this->payload, 'update_id'),
            'message' => $e->getMessage(),
        ]);
    }
}
