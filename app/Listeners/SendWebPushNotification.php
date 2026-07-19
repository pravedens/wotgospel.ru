<?php

namespace App\Listeners;

use App\Chat\MessageSent;
use App\Models\User;
use App\Notifications\ChatMessageNotification;

class SendWebPushNotification
{
    public function handle(MessageSent $event): void
    {
        $receiver = User::find($event->message->receiver_id);
        if (!$receiver) {
            return;
        }

        if (!$receiver->pushSubscriptions()->exists()) {
            return;
        }

        $sender = $event->message->sender;
        $senderName = $sender?->full_name ?? $sender?->name ?? 'Пользователь';

        $receiver->notify(new ChatMessageNotification($event->message, $senderName));
    }
}