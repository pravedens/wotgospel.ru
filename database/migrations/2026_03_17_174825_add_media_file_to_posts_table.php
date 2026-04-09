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
            $table->string('audio_file')->nullable()->after('vkVideo');
            $table->string('audio_filename')->nullable()->after('audio_file');
            $table->bigInteger('audio_size')->nullable()->after('audio_filename');
            $table->string('audio_mime')->nullable()->after('audio_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'audio_file', 'audio_filename', 'audio_size', 'audio_mime'
            ]);
        });
    }
};
