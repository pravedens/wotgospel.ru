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
        Schema::create('bible_verses', function (Blueprint $table) {
            $table->id();
            $table->string('book', 100);
            $table->integer('chapter');
            $table->integer('verse');
            $table->text('text');
            $table->string('book_abbr', 20)->nullable(); // "Ин.", "Быт."
            $table->timestamps();
            
            $table->index(['book', 'chapter', 'verse']);
            $table->index('book_abbr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_verses');
    }
};
