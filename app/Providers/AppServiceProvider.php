<?php

namespace App\Providers;

use App\Listeners\CheckUserAccess;
use App\Models\About;
use App\Models\Event;
use App\Models\Event as EventModel;
use App\Models\Post;
use App\Models\User;
use App\Observers\AboutObserver;
use App\Observers\EventObserver;
use App\Observers\MinisterObserver;
use App\Observers\PostObserver;
use App\Policies\EventPolicy;
use Filament\Support\Facades\FilamentView;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ⭐ РЕГИСТРИРУЕМ WEBPUSH SERVICE PROVIDER
        $this->app->register(WebPushServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::listen('fileUpload:failed', function ($component, $file) {
            logger()->error('Livewire file upload failed', [
                'component' => $component,
                'file' => $file,
            ]);
        });

        Model::unguard();

        // Используем EventFacade вместо Event
        EventFacade::listen(function (Verified $event) {
            return Redirect::to('https://wotgospel.ru');
        });

        // событие для проверки доступа после входа
        EventFacade::listen(
            Login::class,
            CheckUserAccess::class
        );

        // Регистрируем хук для вставки фавиконок в head
        FilamentView::registerRenderHook(
            'panels::head.start',
            fn (): string => view('favicons')->render()
        );

        // Регистрация политик
        Gate::policy(EventModel::class, EventPolicy::class);

        // ✅ Регистрируем Observer для Event
        Event::observe(EventObserver::class);

        // ✅ Регистрируем Observer для Post (добавляем)
        Post::observe(PostObserver::class);

        // ✅ Регистрируем Observer для About
        About::observe(AboutObserver::class);

        User::observe(MinisterObserver::class);

        // Регистрируем WebPush канал
        $this->app->make(ChannelManager::class)->extend('webpush', function ($app) {
            return $app->make(WebPushChannel::class);
        });

        // ====================== ИСПРАВЛЕНИЕ ОШИБКИ ======================
        // Решаем проблему "Route [login] not defined" при использовании Sanctum
        Authenticate::redirectUsing(
            fn () => null
        );
    }
}
