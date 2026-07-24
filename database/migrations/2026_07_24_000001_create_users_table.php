<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('telegram_id')->nullable()->unique();
            $table->string('telegram_username')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('preferred_language', 5)->default('ru');
            $table->string('timezone')->default('Asia/Yerevan');
            $table->string('weight_unit', 5)->default('kg');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
