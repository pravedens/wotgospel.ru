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
        Schema::table('users', function (Blueprint $table) {
            $table->year('enrolled_year')->nullable()->after('assigned_course_id');
            $table->year('graduation_year')->nullable()->after('enrolled_year');
            $table->foreignId('graduated_course_id')->nullable()->after('graduation_year')->constrained('bible_courses')->onDelete('set null');
            $table->timestamp('agreement_accepted_at')->nullable()->after('graduated_course_id');
            $table->string('agreement_ip')->nullable()->after('agreement_accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['enrolled_year', 'graduation_year', 'graduated_course_id', 'agreement_accepted_at', 'agreement_ip']);
        });
    }
};
