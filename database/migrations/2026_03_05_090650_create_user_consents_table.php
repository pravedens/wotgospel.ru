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
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('consent_type')->default('privacy_policy');
            $table->string('policy_version')->nullable(); // Версия политики
            $table->string('ip_address')->nullable(); // IP для доп. защиты
            $table->timestamps(); // created_at будет датой согласия
            
            // Индекс для быстрого поиска
            $table->index(['user_id', 'consent_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};
