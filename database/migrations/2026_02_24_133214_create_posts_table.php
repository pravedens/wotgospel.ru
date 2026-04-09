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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('content');
            $table->integer('category_id')->unsigned();
            $table->string('thumbnail')->nullable();
            $table->string('youtube')->nullable();
            $table->integer('conference_id')->unsigned();
            $table->integer('group_id')->unsigned();
            $table->string('rutube')->nullable();
            $table->string('dzen')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->string('vkVideo')->nullable();
            $table->string('audio')->nullable();
            $table->string('text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
