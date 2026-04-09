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
        Schema::table('events', function (Blueprint $table) {
            // Сначала добавляем поле endDate, если его нет
            if (!Schema::hasColumn('events', 'endDate')) {
                $table->dateTime('endDate')->nullable()->after('startDate');
            }
            
            // Добавляем поля для повторяющихся событий
            if (!Schema::hasColumn('events', 'recurring_type')) {
                $table->string('recurring_type')->nullable()->after('color')
                      ->comment('Тип повторения: daily, weekly, monthly, yearly');
            }
            
            if (!Schema::hasColumn('events', 'recurring_until')) {
                $table->dateTime('recurring_until')->nullable()->after('recurring_type')
                      ->comment('Дата окончания повторений');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['recurring_type', 'recurring_until']);
        });
    }
};
