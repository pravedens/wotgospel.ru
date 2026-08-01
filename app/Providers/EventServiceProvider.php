<?php

namespace App\Providers;

use App\Chat\MessageSent;
use App\Listeners\SendWebPushNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageSent::class => [
            SendWebPushNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
