<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            
            // Временно без foreign key constraints
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('user_id');
            
            $table->json('selected_service_ids')->nullable();
            $table->unsignedSmallInteger('services_count')->default(0);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'waiting'])->default('pending');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('payment_status', 50)->nullable();
            $table->string('payment_id')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            $table->unique(['event_id', 'user_id']);
            $table->index('status');
            $table->index('event_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};