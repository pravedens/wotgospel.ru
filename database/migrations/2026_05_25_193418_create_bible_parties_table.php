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
        Schema::create('bible_parties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->foreignId('course_id')->constrained('bible_courses')->cascadeOnDelete();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->string('join_code', 10)->unique();
            $table->enum('meeting_day', [
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
            ])->nullable();
            $table->time('meeting_time')->nullable();
            $table->string('zoom_link', 500)->nullable();
            $table->integer('max_students')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('join_code');
            $table->index('course_id');
            $table->index('leader_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_parties');
    }
};
