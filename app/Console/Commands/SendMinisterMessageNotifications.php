<?php
// app/Console/Commands/SendMinisterMessageNotifications.php

namespace App\Console\Commands;

use App\Models\MinisterMessage;
use App\Models\MinisterMessageNotificationLog;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendMinisterMessageNotifications extends Command
{
    protected $signature = 'minister-messages:send-notifications 
                            {--message-id= : Send notification only for specific message}
                            {--force : Force resend even if already sent}';
    
    protected $description = 'Send email notifications to ministers about new messages';
    
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }
    
    public function handle()
    {
        // Если указан конкретный ID сообщения
        if ($messageId = $this->option('message-id')) {
            $message = MinisterMessage::find($messageId);
            
            if (!$message) {
                $this->error("Message with ID {$messageId} not found");
                return Command::FAILURE;
            }
            
            $this->processMessage($message);
            $this->info("Processed message ID: {$messageId}");
            return Command::SUCCESS;
        }
        
        // Получаем непрочитанные сообщения за последние 24 часа,
        // по которым ещё не отправлялись уведомления
        $query = MinisterMessage::where('created_at', '>=', now()->subDay())
            ->where('is_read', false);
        
        if (!$this->option('force')) {
            // Исключаем сообщения, по которым уже были отправлены уведомления
            $sentMessageIds = MinisterMessageNotificationLog::where('type', 'new_message')
                ->where('status', 'sent')
                ->pluck('message_id')
                ->toArray();
            
            $query->whereNotIn('id', $sentMessageIds);
        }
        
        $messages = $query->get();
        
        if ($messages->isEmpty()) {
            $this->info('No pending messages to notify');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$messages->count()} messages to process");
        
        $processed = 0;
        $failed = 0;
        
        foreach ($messages as $message) {
            try {
                $this->processMessage($message);
                $processed++;
                $this->line("✓ Notification sent for message ID: {$message->id}");
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Failed for message ID: {$message->id} - " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info("Summary: Processed: {$processed}, Failed: {$failed}");
        
        return Command::SUCCESS;
    }
    
    /**
     * Process single message notification
     */
    protected function processMessage(MinisterMessage $message): void
    {
        $minister = $message->minister;
        
        if (!$minister || !$minister->email) {
            throw new \Exception("Minister has no email");
        }
        
        // Проверяем, не отправляли ли уже
        $alreadySent = MinisterMessageNotificationLog::where('message_id', $message->id)
            ->where('type', 'new_message')
            ->where('status', 'sent')
            ->exists();
        
        if ($alreadySent && !$this->option('force')) {
            $this->line("  - Notification already sent for message {$message->id}, skipping");
            return;
        }
        
        // Отправляем уведомление
        $this->notificationService->sendMinisterMessageNotification($message);
        
        // Логируем в консоль
        $this->line("  - Sent to: {$minister->email}");
        $this->line("  - From: {$message->sender_name} <{$message->sender_email}>");
    }
}