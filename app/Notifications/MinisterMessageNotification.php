<?php

namespace App\Notifications;

use App\Models\MinisterMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MinisterMessageNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct(MinisterMessage $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('✉️ Новое сообщение от прихожанина')
            ->greeting('Здравствуйте, ' . ($notifiable->full_name ?? $notifiable->name) . '!')
            ->line('Вам пришло новое сообщение от прихожанина.')
            ->line('**От:** ' . $this->message->sender_name . ' (' . $this->message->sender_email . ')')
            ->line('**Сообщение:**')
            ->line($this->message->message)
            ->action('Ответить', 'mailto:' . $this->message->sender_email)
            ->line('Вы можете прочитать и ответить на сообщение в личном кабинете.')
            ->salutation('С уважением, ' . config('app.name'));
    }
}