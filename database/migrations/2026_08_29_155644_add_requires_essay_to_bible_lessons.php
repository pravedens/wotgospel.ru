<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bible_lessons', function (Blueprint $table) {
            $table->boolean('requires_essay')->default(false)->after('practice_task');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_lessons', function (Blueprint $table) {
            //
        });
    }
};
