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
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Полиморфная связь (как требует пакет)
            $table->string('subscribable_type');
            $table->unsignedBigInteger('subscribable_id');
            
            // Данные подписки
            $table->string('endpoint', 500)->unique();
            $table->string('public_key', 255)->nullable();
            $table->string('auth_token', 255)->nullable();
            $table->string('content_encoding', 50)->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index(['subscribable_type', 'subscribable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
