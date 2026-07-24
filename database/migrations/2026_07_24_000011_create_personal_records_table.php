<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_set_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('value', 10, 2);
            $table->timestamp('achieved_at');
            $table->timestamps();

            $table->unique(['user_id', 'exercise_id', 'type']);
            $table->index(['user_id', 'type']);
            $table->index(['exercise_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};
