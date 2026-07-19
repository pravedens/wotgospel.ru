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
        Schema::table('bible_enrollment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('bible_enrollment_requests', 'city')) {
                $table->string('city', 255)->nullable();
            }
            if (!Schema::hasColumn('bible_enrollment_requests', 'church_name')) {
                $table->string('church_name', 255)->nullable();
            }
            if (!Schema::hasColumn('bible_enrollment_requests', 'phone')) {
                $table->string('phone', 20)->nullable();
            }
            if (!Schema::hasColumn('bible_enrollment_requests', 'birth_date')) {
                $table->date('birth_date')->nullable();
            }
            if (!Schema::hasColumn('bible_enrollment_requests', 'about')) {
                $table->text('about')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_enrollment_requests', function (Blueprint $table) {
            $table->dropColumn(['city', 'church_name', 'phone', 'birth_date', 'about']);
        });
    }
};
