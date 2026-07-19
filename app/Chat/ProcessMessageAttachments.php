<?php

namespace App\Chat;

use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessMessageAttachments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function handle(): void
    {
        if (!$this->message->attachments) {
            return;
        }

        $processedAttachments = [];

        foreach ($this->message->attachments as $attachment) {
            // Обработка файла (например, генерация миниатюры для изображений)
            if (isset($attachment['type']) && $attachment['type'] === 'image') {
                // Генерация миниатюры
                $processedAttachments[] = [
                    ...$attachment,
                    'thumbnail' => $this->generateThumbnail($attachment['path']),
                ];
            } else {
                $processedAttachments[] = $attachment;
            }
        }

        $this->message->update([
            'attachments' => $processedAttachments,
        ]);
    }

    protected function generateThumbnail(string $path): string
    {
        // Логика генерации миниатюры
        return $path;
    }
}