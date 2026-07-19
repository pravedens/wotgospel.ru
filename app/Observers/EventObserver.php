<?php
// app/Observers/EventObserver.php

namespace App\Observers;

use App\Models\Event;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    /**
     * Handle the event "updated" event.
     */
    public function updated(Event $event): void
    {
        $notificationService = app(NotificationService::class);
        
        $wasPublished = $event->getOriginal('is_published');
        $isNowPublished = $event->is_published;
        
        // 🆕 Проверяем изменение даты
        $originalStartDate = $event->getOriginal('startDate');
        $newStartDate = $event->startDate;
        $dateWasChanged = $originalStartDate != $newStartDate;
        
        // 🆕 Если дата изменилась на будущую - отправляем уведомление как о новом
        $wasPast = $originalStartDate && Carbon::parse($originalStartDate)->isPast();
        $isNowFuture = $newStartDate && Carbon::parse($newStartDate)->isFuture();
        $dateBecameFuture = $wasPast && $isNowFuture;
        
        if ($dateWasChanged && $dateBecameFuture && $isNowPublished) {
            Log::info("Event date changed from past to future - sending new event notification", [
                'event_id' => $event->id,
                'old_date' => $originalStartDate,
                'new_date' => $newStartDate
            ]);
            
            // 🆕 Сбрасываем записанных пользователей при изменении даты
            $event->attendees()->delete();
            Log::info("Event attendees reset due to date change", ['event_id' => $event->id]);
            
            // Отправляем уведомление как о новом событии
            $notificationService->notifyNewEvent($event);
            
            // Обновляем статус
            $event->status = 'active';
            $event->saveQuietly();
        }
        
        // Если событие снято с публикации до наступления даты
        $isBeforeEventDate = $newStartDate && Carbon::parse($newStartDate)->isFuture();
        
        if ($wasPublished === true && $isNowPublished === false && $isBeforeEventDate) {
            Log::info("Event manually unpublished before start date - sending cancellation notifications", [
                'event_id' => $event->id,
                'event_title' => $event->title
            ]);
            
            $notificationService->sendEventCancellationNotifications($event);
            $event->status = 'cancelled';
            $event->saveQuietly();
        }
        
        // Если событие снова опубликовали
        if ($wasPublished === false && $isNowPublished === true) {
            $event->status = 'active';
            $event->saveQuietly();
            $this->sendNotificationIfNeeded($event, 'published', $notificationService);
        }
    }
    
    /**
     * Handle the event "created" event.
     */
    public function created(Event $event): void
    {
        $notificationService = app(NotificationService::class);
        $this->sendNotificationIfNeeded($event, 'created', $notificationService);
    }
    
    /**
     * Отправка уведомления для нового события
     */
    private function sendNotificationIfNeeded(Event $event, string $trigger, NotificationService $notificationService): void
    {
        if (!$event->is_published) {
            return;
        }
        
        if (Carbon::parse($event->startDate)->isPast()) {
            return;
        }
        
        try {
            $notificationService->notifyNewEvent($event);
            Log::info("Notifications sent for event {$event->id}", ['trigger' => $trigger]);
        } catch (\Exception $e) {
            Log::error("Failed to send notifications: " . $e->getMessage());
        }
    }
}