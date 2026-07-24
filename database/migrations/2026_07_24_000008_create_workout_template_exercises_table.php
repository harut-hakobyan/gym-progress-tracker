<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_template_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workout_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->unsignedInteger('target_sets')->nullable();
            $table->unsignedInteger('target_repetitions_min')->nullable();
            $table->unsignedInteger('target_repetitions_max')->nullable();
            $table->decimal('target_weight', 8, 2)->nullable();
            $table->unsignedInteger('rest_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workout_template_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_template_exercises');
    }
};
