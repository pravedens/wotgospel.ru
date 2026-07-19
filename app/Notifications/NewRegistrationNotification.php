<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewRegistrationNotification extends Notification
{
    use Queueable;
    
    protected $event;
    protected $user;
    protected $registration;
    
    public function __construct(Event $event, User $user, $registration)
    {
        $this->event = $event;
        $this->user = $user;
        $this->registration = $registration;
    }
    
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable)
    {
        $servicesCount = count($this->registration->selected_service_ids ?? []);
        
        return (new MailMessage)
            ->subject("🆕 Новая регистрация на конференцию: {$this->event->title}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Пользователь **{$this->user->full_name}** зарегистрировался на конференцию.")
            ->line("**Событие:** {$this->event->title}")
            ->line("**Дата:** " . ($this->event->startDate ? $this->event->startDate->format('d.m.Y') : 'не указана'))
            ->line("**Выбрано служений:** {$servicesCount}")
            ->action('Посмотреть регистрации', url("/admin/event-registrations"))
            ->line('Требуется ваше подтверждение.');
    }
    
    public function toArray($notifiable)
    {
        return [
            'type' => 'new_registration',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'user_id' => $this->user->id,
            'user_name' => $this->user->full_name,
            'registration_id' => $this->registration->id,
            'message' => "Новая регистрация на конференцию \"{$this->event->title}\" от {$this->user->full_name}",
        ];
    }
}