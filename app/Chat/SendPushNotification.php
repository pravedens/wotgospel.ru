<?php

namespace App\Chat;

use App\Models\User;
use App\Notifications\Chat\NewMessageNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPushNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MessageSent $event): void
    {
        $receiver = User::find($event->message->receiver_id);
        if (!$receiver) {
            return;
        }

        // Отправляем push уведомление если есть подписка
        if ($receiver->pushSubscriptions()->exists()) {
            $receiver->notify(new NewMessageNotification(
                $event->message->sender->full_name,
                $event->message->message
            ));
        }

        // Отправляем email если включено
        if ($receiver->email_verified_at && $receiver->notify_new_messages_email) {
            // Отправка email
        }
    }
}