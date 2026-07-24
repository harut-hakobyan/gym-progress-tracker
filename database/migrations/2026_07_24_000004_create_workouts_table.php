<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workout_template_id')->nullable()->index();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('user_body_weight', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
