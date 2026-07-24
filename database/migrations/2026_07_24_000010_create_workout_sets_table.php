<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_sets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('set_number');
            $table->string('type')->default('working');
            $table->decimal('weight', 8, 2);
            $table->unsignedInteger('repetitions');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->unsignedTinyInteger('rpe')->nullable();
            $table->unsignedTinyInteger('rir')->nullable();
            $table->unsignedInteger('rest_seconds')->nullable();
            $table->boolean('is_completed')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workout_exercise_id', 'set_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
    }
};
