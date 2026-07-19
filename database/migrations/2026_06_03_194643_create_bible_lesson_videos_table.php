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
        Schema::create('bible_lesson_videos', function (Blueprint $table) {
           $table->id();
            $table->foreignId('bible_lesson_id')->constrained('bible_lessons')->onDelete('cascade');  // ← правильное имя
            $table->string('title')->nullable();
            $table->string('url');
            $table->string('platform')->nullable();
            $table->string('video_id')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_lesson_videos');
    }
};
