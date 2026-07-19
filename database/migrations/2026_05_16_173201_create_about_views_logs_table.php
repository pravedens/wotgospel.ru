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
        Schema::create('about_views_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('about_id');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->date('viewed_at');
            $table->timestamps();
            
            // Уникальная комбинация: статья + IP + дата
            $table->unique(['about_id', 'ip_address', 'viewed_at'], 'about_views_unique');
            
            // Просто индекс вместо внешнего ключа
            $table->index('about_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_views_logs');
    }
};
