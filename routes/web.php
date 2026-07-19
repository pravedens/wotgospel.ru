<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\FilamentVerificationController;
use App\Http\Controllers\Admin\CertificatePreviewController;

Route::get("/test-mail", function () {
    $user = App\Models\User::find(83);
    if ($user) {
        $user->sendEmailVerificationNotification();
        return "Email sent to " . $user->email;
    }
    return "User not found";
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/email-verification/verify/{id}/{hash}', [FilamentVerificationController::class, 'verify'])
    ->name('filament.admin.auth.email-verification.verify');
    
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    
    // Всегда редиректим на главную страницу
    return redirect('/');
})->name('logout')->middleware('web');

Route::get('/reset-password/{token}', function ($token) {
    $email = request()->email;
    return redirect("https://wotnt.ru/auth/reset-password?token={$token}&email={$email}");
})->name('password.reset');

Route::get('/admin/certificate-preview/{course}', [CertificatePreviewController::class, 'preview'])
    ->middleware('auth')
    ->name('certificate.preview');