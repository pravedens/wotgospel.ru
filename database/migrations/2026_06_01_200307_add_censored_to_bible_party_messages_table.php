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
        Schema::table('bible_party_messages', function (Blueprint $table) {
            $table->boolean('is_censored')->default(false)->after('message');
            $table->text('original_message')->nullable()->after('is_censored');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bible_party_messages', function (Blueprint $table) {
            $table->dropColumn(['is_censored', 'original_message']);
        });
    }
};
