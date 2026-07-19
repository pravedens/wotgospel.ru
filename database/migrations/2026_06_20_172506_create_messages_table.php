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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // Связи
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            
            // Контент
            $table->text('message');
            $table->string('type')->default('text'); // text, image, file, system
            
            // Вложения (JSON)
            $table->json('attachments')->nullable();
            
            // Статусы
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_delivered')->default(false);
            $table->timestamp('delivered_at')->nullable();
            
            // Модерация (для групповых чатов)
            $table->boolean('is_approved')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Цензура
            $table->boolean('is_censored')->default(false);
            $table->text('original_message')->nullable();
            
            // Для системы
            $table->boolean('is_system')->default(false);
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index(['conversation_id', 'created_at']);
            $table->index('sender_id');
            $table->index('receiver_id');
            $table->index('is_read');
            $table->index(['conversation_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
