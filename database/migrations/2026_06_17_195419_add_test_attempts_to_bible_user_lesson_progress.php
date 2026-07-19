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
        Schema::table('bible_user_lesson_progress', function (Blueprint $table) {
            $table->integer('test_attempts')->default(0)->after('test_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_user_lesson_progress', function (Blueprint $table) {
            $table->dropColumn('test_attempts');
        });
    }
};
