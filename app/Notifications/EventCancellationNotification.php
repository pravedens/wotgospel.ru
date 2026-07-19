<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use Carbon\Carbon;

class EventCancellationNotification extends Notification
{
    use Queueable;
    
    protected $event;
    
    public function __construct(Event $event)
    {
        $this->event = $event;
    }
    
    public function via($notifiable)
    {
        $channels = [];
        
        if ($notifiable->wantsNotification('reminder', 'email')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->wantsNotification('reminder', 'webpush')) {
            $channels[] = WebPushChannel::class;
        }
        
        // SMS через NotificationService будет отдельно
        
        return $channels;
    }
    
    public function toMail($notifiable)
    {
        $eventDate = $this->event->startDate 
            ? Carbon::parse($this->event->startDate)->translatedFormat('d F Y')
            : 'дата не указана';
        
        $eventTime = $this->event->startTime 
            ? Carbon::parse($this->event->startTime)->format('H:i')
            : '';
        
        return (new MailMessage)
            ->subject("❌ Событие отменено: {$this->event->title}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("К сожалению, событие **«{$this->event->title}»** было отменено.")
            ->line("**Дата:** {$eventDate}")
            ->line($eventTime ? "**Время:** {$eventTime}" : "")
            ->line("Приносим извинения за неудобства.")
            ->action('Посмотреть другие события', url('/events'))
            ->line('Следите за обновлениями в нашем календаре!');
    }
    
    public function toWebPush($notifiable, $notification)
    {
        $eventUrl = $this->event->slug 
            ? "https://wotnt.ru/events/{$this->event->slug}"
            : "https://wotnt.ru/events";
        
        return (new WebPushMessage)
            ->title("❌ Событие отменено")
            ->body("{$this->event->title} было отменено. Приносим извинения.")
            ->icon('/favicon/favicon-32.png')
            ->badge('/favicon/favicon-32.png')
            ->data([
                'url' => $eventUrl,
                'event_id' => $this->event->id,
                'type' => 'cancellation',
            ])
            ->action('Открыть календарь', 'open');
    }
}