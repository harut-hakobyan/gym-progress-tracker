<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_telegram_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state');
            $table->json('payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['state', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_telegram_states');
    }
};
