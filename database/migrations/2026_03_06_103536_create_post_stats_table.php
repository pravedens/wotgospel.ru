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
        Schema::create('post_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('post_id');
            $table->string('ip', 45); // IPv6 поддержка
            $table->string('user_agent', 500);
            $table->string('fingerprint', 100)->nullable(); // уникальный отпечаток
            $table->boolean('viewed')->default(false);
            $table->boolean('liked')->default(false);
            $table->timestamp('viewed_at')->nullable();
            $table->date('viewed_at_date')->nullable(); // Добавляем поле для даты
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['post_id', 'ip', 'user_agent']);
            $table->index(['post_id', 'fingerprint']);
            $table->index('viewed_at_date');
            
            // Уникальность - один просмотр с одного устройства в день
            $table->unique(['post_id', 'ip', 'user_agent', 'viewed_at_date'], 'unique_view_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_stats');
    }
};
