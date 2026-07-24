<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('muscle_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'slug']);
            $table->index(['muscle_group_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
