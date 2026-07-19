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
            // Добавляем дату служения
            if (!Schema::hasColumn('conference_services', 'service_date')) {
                $table->date('service_date')->nullable()->after('event_id');
            }
            
            // Добавляем количество мест
            if (!Schema::hasColumn('conference_services', 'capacity')) {
                $table->unsignedInteger('capacity')->default(0)->after('speaker');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_services', function (Blueprint $table) {
            $table->dropColumn(['service_date', 'capacity']);
        });
    }
};
