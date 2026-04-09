<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event as EventFacade; // Алиас для фасада Event
use App\Models\Event as EventModel; // Алиас для модели Event
use App\Policies\EventPolicy;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Auth\Events\Login;
use App\Listeners\CheckUserAccess;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::listen('fileUpload:failed', function ($component, $file) {
        logger()->error('Livewire file upload failed', [
            'component' => $component,
            'file' => $file
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
        
        // Регистрация политик - используем EventModel вместо Event
        \Illuminate\Support\Facades\Gate::policy(EventModel::class, EventPolicy::class);
    }
}