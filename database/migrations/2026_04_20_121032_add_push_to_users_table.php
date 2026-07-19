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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_new_events_webpush')->default(false);
            $table->boolean('notify_event_reminder_webpush')->default(false);
            $table->boolean('notify_event_day_webpush')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_new_events_webpush',
                'notify_event_reminder_webpush',
                'notify_event_day_webpush',
            ]);
        });
    }
};
