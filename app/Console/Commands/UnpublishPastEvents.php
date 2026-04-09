<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UnpublishPastEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:unpublish-past';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Снимает с публикации прошедшие события';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Начинаем снятие с публикации прошедших событий...');
        
        // Находим все опубликованные события, которые уже прошли
        $pastEvents = Event::where('is_published', true)
            ->whereDate('startDate', '<', Carbon::today())
            ->get();
        
        $count = $pastEvents->count();
        
        if ($count === 0) {
            $this->info('Нет прошедших событий для снятия с публикации.');
            return Command::SUCCESS;
        }
        
        $this->info("Найдено {$count} прошедших событий.");
        
        // Снимаем с публикации
        foreach ($pastEvents as $event) {
            $event->is_published = false;
            $event->save();
            
            $this->line("✓ Снято с публикации: {$event->title} (ID: {$event->id})");
        }
        
        $this->info("✅ Успешно снято с публикации {$count} событий.");
        
        return Command::SUCCESS;
    }
}