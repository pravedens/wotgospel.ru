<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Chat\MessageSent::class => [
            \App\Listeners\SendWebPushNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}