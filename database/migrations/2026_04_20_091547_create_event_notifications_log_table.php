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
        Schema::create('event_notifications_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('event_id');  // ⚠️ INT UNSIGNED для совместимости с events.id
            $table->string('type', 50);
            $table->string('channel', 20);
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Индексы
            $table->index(['user_id', 'event_id', 'type']);
            $table->index('status');
            $table->index('created_at');
            
            // ✅ ТОЛЬКО внешний ключ на user_id
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_notifications_log');
    }
};
