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
            $table->timestamp('reset_at')->nullable()->after('attended_by_leader_at');
            $table->string('reset_reason')->nullable()->after('reset_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_user_lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['reset_at', 'reset_reason']);
        });
    }
};
