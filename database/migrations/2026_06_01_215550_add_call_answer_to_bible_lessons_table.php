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
            $table->text('call_answer')->nullable()->after('call_question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_lessons', function (Blueprint $table) {
            $table->dropColumn('call_answer');
        });
    }
};
