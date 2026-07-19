<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========== УВЕДОМЛЕНИЯ О СОБЫТИЯХ ==========

// Уведомления о новых событиях (каждые 10 минут)
Schedule::command('events:send-new-event-notifications')->everyTenMinutes();

// Напоминания за день до и за 2 часа до события (каждые 15 минут)
Schedule::command('events:send-notifications')->everyFifteenMinutes();

// Снятие с публикации прошедших событий (раз в день в 1:00)
Schedule::command('events:unpublish-past')->dailyAt('01:00');

// Очистка старых событий (раз в день в 3:00)
Schedule::command('events:cleanup-expired --days=30 --force')->dailyAt('03:00');