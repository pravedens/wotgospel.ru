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
        Schema::create('bible_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('bible_courses')->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            
            // Дугообразная модель
            $table->text('call_question')->nullable();      // Призыв
            $table->text('scripture_verses')->nullable();   // Писание
            $table->longText('content')->nullable();        // HTML контент урока (с картинками)
            $table->text('practice_task')->nullable();      // Задание на неделю
            
            // Видео
            $table->string('video_url', 500)->nullable();
            $table->string('video_platform', 50)->default('rutube'); // rutube, vk, youtube
            $table->string('video_id', 100)->nullable();    // ID видео на платформе
            
            // PDF
            $table->string('pdf_conspect_url', 500)->nullable();
            
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            
            $table->index(['course_id', 'order']);
            $table->index('slug');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_lessons');
    }
};
