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
        Schema::create('bible_user_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('bible_lessons')->cascadeOnDelete();
            
            $table->enum('status', [
                'not_started',
                'call_completed',
                'scripture_completed',
                'video_watched',
                'practice_completed',
                'test_passed',
                'completed'
            ])->default('not_started');
            
            $table->timestamp('video_watched_at')->nullable();
            $table->timestamp('practice_completed_at')->nullable();
            $table->timestamp('test_passed_at')->nullable();
            $table->integer('test_score')->nullable();
            $table->timestamp('attended_by_leader_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'status']);
            $table->index('attended_by_leader_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_user_lesson_progress');
    }
};
