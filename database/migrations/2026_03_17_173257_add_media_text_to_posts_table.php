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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('text_file')->nullable()->after('text');
            $table->string('text_filename')->nullable()->after('text_file');
            $table->bigInteger('text_size')->nullable()->after('text_filename');
            $table->string('text_mime')->nullable()->after('text_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'text_file', 'text_filename', 'text_size', 'text_mime'
            ]);
        });
    }
};
