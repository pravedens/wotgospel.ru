<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DeleteExpiredEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Удаляем изображение
            if ($this->event->thumbnail) {
                $imagePath = str_replace('public/', '', $this->event->thumbnail);
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }
            
            // Удаляем событие
            $this->event->delete();
            
        } catch (\Exception $e) {
            Log::error("Failed to delete expired event in job", [
                'event_id' => $this->event->id,
                'error' => $e->getMessage()
            ]);
            
            // Пробуем снова через 5 минут
            $this->release(300);
        }
    }
}