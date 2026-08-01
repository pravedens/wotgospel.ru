<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\CustomVerifyEmail;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

// ✅ Правильный импорт

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),

            // Действие для ручного подтверждения email (администратором)
            Action::make('verify_email')
                ->label('Подтвердить email вручную')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => ! $this->record->hasVerifiedEmail())
                ->requiresConfirmation()
                ->modalHeading('Подтверждение email')
                ->modalDescription('Вы уверены, что хотите вручную подтвердить email этого пользователя?')
                ->action(function () {
                    $this->record->markEmailAsVerified();

                    Notification::make()
                        ->title('Email подтверждён')
                        ->body('Email пользователя успешно подтверждён администратором')
                        ->success()
                        ->send();
                }),

            // Действие для отправки письма с подтверждением
            Action::make('send_verification')
                ->label('Отправить письмо с подтверждением')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->visible(fn (): bool => ! $this->record->hasVerifiedEmail())
                ->requiresConfirmation()
                ->modalHeading('Отправка письма')
                ->modalDescription('Отправить пользователю письмо со ссылкой для подтверждения email?')
                ->action(function () {
                    $user = $this->record;

                    // ✅ Используем CustomVerifyEmail вместо VerifyEmail
                    $user->notify(new CustomVerifyEmail);

                    Notification::make()
                        ->title('Письмо отправлено')
                        ->body("Ссылка для подтверждения отправлена на {$user->email}")
                        ->success()
                        ->send();
                }),
        ];
    }

    // Перенаправление после сохранения
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Уведомление после сохранения
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Пользователь обновлён')
            ->body('Изменения успешно сохранены');
    }
}
