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
        Schema::create('minister_message_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('minister_messages')->onDelete('cascade');
            $table->foreignId('minister_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // new_message, reminder
            $table->string('channel'); // email, push, webpush
            $table->string('status'); // pending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['message_id', 'type', 'status']);
            $table->index(['minister_id', 'status']);
            $table->index(['channel', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minister_message_notification_logs');
    }
};
