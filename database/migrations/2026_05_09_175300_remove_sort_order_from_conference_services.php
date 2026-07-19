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
        Schema::table('conference_services', function (Blueprint $table) {
            if (Schema::hasColumn('conference_services', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_services', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('capacity');
        });
    }
};
