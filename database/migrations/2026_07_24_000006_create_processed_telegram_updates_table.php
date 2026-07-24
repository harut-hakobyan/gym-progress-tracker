<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_telegram_updates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('update_id')->unique();
            $table->unsignedBigInteger('telegram_id')->nullable()->index();
            $table->string('action_type')->nullable();
            $table->string('status')->default('received');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['telegram_id', 'action_type']);
            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_telegram_updates');
    }
};
