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
        Schema::create('bible_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('bible_courses')->cascadeOnDelete();
            $table->uuid('certificate_uuid')->unique();
            $table->string('qrcode_url', 500)->nullable();
            $table->string('pdf_url', 500)->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['user_id', 'course_id']);
            $table->index('certificate_uuid');
            $table->index('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_certificates');
    }
};
