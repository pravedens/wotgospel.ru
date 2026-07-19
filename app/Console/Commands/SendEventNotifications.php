<?php
// app/Console/Commands/SendEventNotifications.php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendEventNotifications extends Command
{
    protected $signature = 'events:send-notifications';
    protected $description = 'Send event notifications to users';
    
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }
    
    public function handle()
    {
        $this->info('Sending day before reminders...');
        $this->notificationService->sendDayBeforeReminders();
        
        // Короткая пауза между разными типами уведомлений
        usleep(500000); // 0.5 секунды
        
        $this->info('Sending 2-hour reminders...');
        $this->notificationService->sendDayOfEventReminders();
        
        $this->info('Done!');
        
        return Command::SUCCESS;
    }
}