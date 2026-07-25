<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table): void {
            $table->string('media_type', 32)->nullable()->after('description');
            $table->text('media_value')->nullable()->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table): void {
            $table->dropColumn(['media_type', 'media_value']);
        });
    }
};
