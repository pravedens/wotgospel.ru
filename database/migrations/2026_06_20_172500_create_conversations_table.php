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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            
            // Участники
            $table->foreignId('user1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user2_id')->constrained('users')->cascadeOnDelete();
            
            // Тип беседы
            $table->enum('type', ['private', 'teacher_student', 'group'])->default('private');
            
            // Группа (если тип 'group')
            $table->foreignId('party_id')->nullable()->constrained('bible_parties')->nullOnDelete();
            
            // Метаданные
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_read_at_user1')->nullable();
            $table->timestamp('last_read_at_user2')->nullable();
            
            // Статусы
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived_user1')->default(false);
            $table->boolean('is_archived_user2')->default(false);
            
            $table->timestamps();
            
            // Уникальность: одна беседа между двумя пользователями
            $table->unique(['user1_id', 'user2_id']);
            
            // Индексы
            $table->index(['user1_id', 'user2_id']);
            $table->index('type');
            $table->index('party_id');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
