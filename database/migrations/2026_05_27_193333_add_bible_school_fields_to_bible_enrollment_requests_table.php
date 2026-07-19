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
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('ministry', 255)->nullable();
            $table->text('bible_courses_experience')->nullable();
            $table->text('learning_expectations')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_enrollment_requests', function (Blueprint $table) {
            $table->dropColumn([
                'marital_status',
                'gender',
                'ministry',
                'bible_courses_experience',
                'learning_expectations'
            ]);
        });
    }
};
