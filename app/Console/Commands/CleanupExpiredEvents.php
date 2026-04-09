<?php

namespace App\Console\Commands;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupExpiredEvents extends Command
{
    /**
     * Имя и сигнатура команды
     *
     * @var string
     */
    protected $signature = 'events:cleanup-expired 
                            {--days=30 : Количество дней после окончания для удаления}
                            {--force : Удалить без подтверждения}
                            {--dry-run : Показать, что будет удалено без фактического удаления}';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Удаляет прошедшие события и связанные с ними изображения';

    /**
     * Выполнение команды
     */
    public function handle()
    {
        $days = $this->option('days');
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        // Определяем дату, до которой события считаются устаревшими
        $expiredDate = Carbon::now()->subDays($days)->endOfDay();
        
        $this->info("Поиск событий, завершившихся до {$expiredDate->format('d.m.Y H:i:s')}");
        
        // Находим события, которые:
        // 1. Произошли раньше expiredDate
        // 2. Не являются повторяющимися (recurring_type = null)
        $expiredEvents = Event::where('startDate', '<', $expiredDate)
            ->whereNull('recurring_type') // Только не повторяющиеся
            ->orWhere(function ($query) use ($expiredDate) {
                $query->where('endDate', '<', $expiredDate)
                      ->whereNull('recurring_type');
            })
            ->get();
        
        $count = $expiredEvents->count();
        
        if ($count === 0) {
            $this->info("Нет событий для удаления.");
            return 0;
        }
        
        $this->warn("Найдено {$count} событий для удаления:");
        
        // Показываем список событий, которые будут удалены
        $eventsTable = [];
        foreach ($expiredEvents as $event) {
            $eventsTable[] = [
                $event->id,
                $event->title,
                $event->startDate->format('d.m.Y'),
                $event->thumbnail ? 'Да' : 'Нет',
                $event->recurring_type ?? '-',
            ];
        }
        
        $this->table(['ID', 'Название', 'Дата', 'Есть фото', 'Повторение'], $eventsTable);
        
        if ($isDryRun) {
            $this->info("Сухой запуск: ничего не удалено.");
            return 0;
        }
        
        // Запрашиваем подтверждение
        if (!$force && !$this->confirm("Удалить {$count} событий? Это действие нельзя отменить.")) {
            $this->info("Операция отменена.");
            return 0;
        }
        
        // Счетчики для статистики
        $deletedCount = 0;
        $deletedImages = 0;
        $failedCount = 0;
        
        // Удаляем события
        foreach ($expiredEvents as $event) {
            try {
                // Удаляем изображение, если оно есть
                if ($event->thumbnail) {
                    $imagePath = str_replace('public/', '', $event->thumbnail);
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                        $deletedImages++;
                        $this->line("Удалено изображение: {$imagePath}");
                    }
                }
                
                // Удаляем само событие
                $event->delete();
                $deletedCount++;
                
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("Ошибка при удалении события ID {$event->id}: {$e->getMessage()}");
                Log::error("Failed to delete expired event ID {$event->id}", [
                    'error' => $e->getMessage(),
                    'event' => $event->toArray()
                ]);
            }
        }
        
        // Итоговая статистика
        $this->info("Операция завершена:");
        $this->info("- Удалено событий: {$deletedCount}");
        $this->info("- Удалено изображений: {$deletedImages}");
        if ($failedCount > 0) {
            $this->warn("- Ошибок: {$failedCount}");
        }
        
        // Логируем результат
        Log::info('Events cleanup completed', [
            'deleted_events' => $deletedCount,
            'deleted_images' => $deletedImages,
            'failed' => $failedCount,
            'days_threshold' => $days
        ]);
        
        return 0;
    }
}