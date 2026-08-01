<?php

namespace App\Providers;

use App\Filament\Pages\Auth\CustomResetPassword;
use Filament\Pages\Auth\PasswordReset\ResetPassword;
use Illuminate\Support\ServiceProvider;

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
