<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Pages\Auth\PasswordReset\ResetPassword;
use App\Filament\Pages\Auth\CustomResetPassword;

class FilamentAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Переопределяем класс для страницы сброса пароля
        $this->app->bind(ResetPassword::class, CustomResetPassword::class);
    }
}