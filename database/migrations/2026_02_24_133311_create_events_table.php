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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->date('startDate')->nullable();
            $table->text('startWeek')->nullable();
            $table->text('startDay')->nullable();
            $table->text('startMonth')->nullable();
            $table->time('startTime')->nullable();
            $table->text('description');
            $table->text('content');
            $table->string('thumbnail')->nullable();
            $table->timestamps();
            $table->text('info')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
