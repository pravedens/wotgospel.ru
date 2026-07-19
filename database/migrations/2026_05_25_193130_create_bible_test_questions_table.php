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
        Schema::create('bible_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('bible_lessons')->cascadeOnDelete();
            
            $table->enum('type', [
                'single_choice',
                'multiple_choice',
                'matching',
                'ordering',
                'odd_one_out',
                'verse_reference',
                'select_verse',
                'true_false',
                'fill_blank'
            ])->default('single_choice');
            
            $table->text('question');
            $table->json('config'); // конфигурация вопроса (зависит от типа)
            $table->integer('points')->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['lesson_id', 'order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_test_questions');
    }
};
