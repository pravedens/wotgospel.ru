<?php

namespace App\Chat;

use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMessageNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function handle(): void
    {
        try {
            $receiver = User::find($this->message->receiver_id);

            if (!$receiver) {
                return;
            }

            $sender = $this->message->sender;

            $senderName = $sender?->full_name
                ?? $sender?->name
                ?? 'Пользователь';

            $messageText = $this->message->message ?? '';

            // Email уведомление
            if ($receiver->email_verified_at && $receiver->notify_new_messages_email) {
                // Здесь позже можно добавить отправку email
            }

            // Уведомление в database
            $receiver->notify(new NewMessageNotification(
                $senderName,
                $messageText
            ));
        } catch (Throwable $e) {
            Log::error('SendMessageNotifications failed', [
                'message_id' => $this->message->id ?? null,
                'receiver_id' => $this->message->receiver_id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
