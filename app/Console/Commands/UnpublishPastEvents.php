<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UnpublishPastEvents extends Command
{
    protected $signature = 'events:unpublish-past';
    protected $description = 'Снимает с публикации события, которые уже полностью прошли (без уведомлений)';

    public function handle()
    {
        $this->info('Начинаем снятие с публикации прошедших событий...');
        
        $now = Carbon::now();
        
        $this->info('Текущее время сервера: ' . $now->format('Y-m-d H:i:s'));
        
        // Находим все опубликованные события, которые уже прошли
        $pastEvents = Event::where('is_published', true)
            ->where('status', 'active')
            ->whereRaw(
                "CONCAT(startDate, ' ', COALESCE(startTime, '00:00:00')) < ?",
                [$now->toDateTimeString()]
            )
            ->get();
        
        $count = $pastEvents->count();
        
        if ($count === 0) {
            $this->info('Нет прошедших событий для снятия с публикации.');
            return Command::SUCCESS;
        }
        
        $this->info("Найдено {$count} прошедших событий.");
        
        $unpublishedCount = 0;
        
        foreach ($pastEvents as $event) {
            // 🆕 Снимаем с публикации БЕЗ отправки уведомлений
            $event->is_published = false;
            $event->status = 'past'; // или оставляем 'active', но is_published = false
            $event->saveQuietly(); // 👈 Используем saveQuietly() чтобы не триггерить observer
            
            $this->line("✓ Снято с публикации: {$event->title} (ID: {$event->id})");
            $unpublishedCount++;
        }
        
        $this->info("✅ Успешно снято с публикации {$unpublishedCount} из {$count} событий.");
        
        return Command::SUCCESS;
    }
}