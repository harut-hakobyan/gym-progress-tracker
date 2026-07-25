<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_templates', function (Blueprint $table): void {
            $table->unsignedTinyInteger('day_of_week')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('workout_templates', function (Blueprint $table): void {
            $table->dropColumn('day_of_week');
        });
    }
};
