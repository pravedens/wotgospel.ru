<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendEventCancellationNotifications extends Command
{
    protected $signature = 'events:send-cancellation-notifications {eventId?}';
    protected $description = 'Send cancellation notifications to event attendees';
    
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }
    
    public function handle()
    {
        $eventId = $this->argument('eventId');
        
        if ($eventId) {
            $event = Event::find($eventId);
            if (!$event) {
                $this->error("Event with ID {$eventId} not found");
                return Command::FAILURE;
            }
            
            $this->info("Sending cancellation notifications for event: {$event->title}");
            $this->notificationService->sendEventCancellationNotifications($event);
            $this->info("Done!");
            
            return Command::SUCCESS;
        }
        
        // Отправляем для всех отменённых событий
        $cancelledEvents = Event::where('status', 'cancelled')
            ->where('is_published', false)
            ->whereDate('startDate', '>=', now())
            ->get();
        
        foreach ($cancelledEvents as $event) {
            $this->info("Sending for event: {$event->title}");
            $this->notificationService->sendEventCancellationNotifications($event);
        }
        
        $this->info("Completed for {$cancelledEvents->count()} events");
        
        return Command::SUCCESS;
    }
}