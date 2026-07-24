<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workout_id', 'position']);
            $table->index(['exercise_id', 'workout_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
