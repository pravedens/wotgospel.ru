// app/Notifications/NewPrivateMessageNotification.php

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class NewPrivateMessageNotification extends Notification
{
    use Queueable;

    protected $senderName;
    protected $message;

    public function __construct(string $senderName, string $message)
    {
        $this->senderName = $senderName;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title("✉️ Сообщение от {$this->senderName}")
            ->body(mb_substr($this->message, 0, 100))
            ->icon('/favicon/icon-192.png')
            ->badge('/favicon/favicon-32.png')
            ->data([
                'url' => 'https://wotnt.ru/dashboard',
                'type' => 'private_message',
                'sender' => $this->senderName,
            ]);
    }
}