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
            // Настройки уведомлений
            $table->boolean('notify_new_events_email')->default(false);
            $table->boolean('notify_new_events_push')->default(false);
            $table->boolean('notify_event_reminder_email')->default(false);
            $table->boolean('notify_event_reminder_push')->default(false);
            $table->boolean('notify_event_day_email')->default(false);
            $table->boolean('notify_event_day_push')->default(false);
            
            // Согласие на рассылку
            $table->timestamp('notification_consent_given_at')->nullable();
            $table->string('notification_consent_ip', 45)->nullable();
            
            // Телефон для push-уведомлений
            $table->string('phone_for_notifications', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_new_events_email',
                'notify_new_events_push',
                'notify_event_reminder_email',
                'notify_event_reminder_push',
                'notify_event_day_email',
                'notify_event_day_push',
                'notification_consent_given_at',
                'notification_consent_ip',
                'phone_for_notifications',
            ]);
        });
    }
};
