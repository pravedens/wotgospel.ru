<?php
// app/Notifications/EventPushNotification.php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class EventPushNotification extends Notification
{
    protected $event;
    protected $type;
    
    public function __construct($event, string $type)
    {
        $this->event = $event;
        $this->type = $type;
    }
    
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }
    
    public function toWebPush($notifiable, $notification)
    {
        // Формируем правильный URL для события
        $eventUrl = $this->event->id && $this->event->slug 
            ? "https://wotnt.ru/events/{$this->event->slug}"
            : "https://wotnt.ru/events";
        
        return (new WebPushMessage)
            ->title($this->getTitle())
            ->body($this->getBody())
            ->icon('/favicon/favicon-32.png')
            ->badge('/favicon/favicon-32.png')
            ->data([
                'url' => $eventUrl,
                'event_id' => $this->event->id,
                'type' => $this->type,
            ])
            ->action('Открыть', 'open')
            ->action('Закрыть', 'close');
    }
    
    protected function getTitle(): string
    {
        return match($this->type) {
            'new_event' => '🆕 Новое событие',
            'reminder' => '🔔 Напоминание',
            'day_before' => '📅 Событие завтра',
            default => $this->event->title ?? 'Уведомление',
        };
    }
    
    protected function getBody(): string
    {
        $time = $this->event->startTime 
            ? \Carbon\Carbon::parse($this->event->startTime)->format('H:i')
            : '';
        
        return match($this->type) {
            'new_event' => ($this->event->title ?? 'Событие') . ($time ? " в {$time}" : ""),
            'reminder' => ($this->event->title ?? 'Событие') . " сегодня" . ($time ? " в {$time}" : ""),
            'day_before' => ($this->event->title ?? 'Событие') . " завтра" . ($time ? " в {$time}" : ""),
            default => $this->event->title ?? 'Уведомление',
        };
    }
}