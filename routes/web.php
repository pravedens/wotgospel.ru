<?php

use App\Http\Controllers\Admin\CertificatePreviewController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Auth\FilamentVerificationController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/test-mail', function () {
    $user = User::find(83);
    if ($user) {
        $user->sendEmailVerificationNotification();

        return 'Email sent to '.$user->email;
    }

    return 'User not found';
});

Route::get('/test-mail-debug', function () {
    Mail::raw('Тестовое письмо из Debug Mail', function ($message) {
        $message->to('test@example.com')
            ->subject('Тест Debug Mail');
    });

    return 'Письмо отправлено!';
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/email-verification/verify/{id}/{hash}', [FilamentVerificationController::class, 'verify'])
    ->name('filament.admin.auth.email-verification.verify');

Route::get('/reset-password/{token}', function ($token) {
    $email = request()->email;

    return redirect("https://wotnt.ru/auth/reset-password?token={$token}&email={$email}");
})->name('password.reset');

Route::get('/admin/certificate-preview/{course}', [CertificatePreviewController::class, 'preview'])
    ->middleware('auth')
    ->name('certificate.preview');

// ============================================
// SANCTUM SPA AUTHENTICATION (cookie-based)
// ============================================
// Эти маршруты ДОЛЖНЫ быть в web.php, чтобы работала сессия Laravel.
// Префикса /api у них нет — фронтенд обращается напрямую на backendUrl.

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

// Защищённые auth-маршруты
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Email верификация
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');
