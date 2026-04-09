<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class FilamentVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        Log::info('=== ПОДТВЕРЖДЕНИЕ ЧЕРЕЗ FILAMENT ===');
        Log::info('ID: ' . $id);
        Log::info('Hash: ' . $hash);
        
        $user = \App\Models\User::find($id);
        
        if (!$user) {
            Log::error('Пользователь не найден');
            return redirect()->to('https://wotgospel.ru');
        }

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            Log::error('Неверная подпись');
            return redirect()->to('https://wotgospel.ru');
        }

        if ($user->markEmailAsVerified()) {
            Log::info('Email подтверждён для: ' . $user->email);
            
            // Filament уведомление
            \Filament\Notifications\Notification::make()
                ->title('Email подтверждён!')
                ->body('Ваш email успешно подтверждён. Теперь вы можете войти в систему.')
                ->success()
                ->persistent()
                ->send();
        }

        return redirect()->to('https://wotgospel.ru');
    }
}