<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('target_value', 10, 2);
            $table->date('target_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_goals');
    }
};
