<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Auth\VerifyEmail;
use Illuminate\Support\Facades\URL;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected function afterCreate(): void
    {
        $user = $this->record;
        
        // Отправляем письмо с подтверждением
        $verificationUrl = URL::temporarySignedRoute(
            'filament.admin.auth.email-verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
        
        $notification = new VerifyEmail();
        $notification->url = $verificationUrl;
        $user->notify($notification);
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
