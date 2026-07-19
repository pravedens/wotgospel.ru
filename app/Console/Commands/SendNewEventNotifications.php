<?php
// app/Console/Commands/SendNewEventNotifications.php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventNotificationLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendNewEventNotifications extends Command
{
    protected $signature = 'events:send-new-event-notifications';
    protected $description = 'Send notifications about new events';
    
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }
    
    public function handle()
    {
        // Получаем события, созданные за последние 10 минут, 
        // опубликованные и не прошедшие
        $recentEvents = Event::where('created_at', '>=', now()->subMinutes(10))
            ->where('is_published', true)
            ->whereDate('startDate', '>=', now())
            ->get();
        
        foreach ($recentEvents as $event) {
            $alreadySent = EventNotificationLog::where('event_id', $event->id)
                ->where('type', 'new_event')
                ->exists();
            
            if (!$alreadySent) {
                $this->info("Sending notifications for new event: {$event->title}");
                $this->notificationService->notifyNewEvent($event);
            }
        }
        
        $this->info('Done!');
        
        return Command::SUCCESS;
    }
}