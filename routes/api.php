<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\AvatarController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\ConferenceController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\DenominationController;
use App\Http\Controllers\Api\DocumentProxyController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\BibleController;
use App\Http\Controllers\Api\ContactsController;
use App\Http\Controllers\Api\LiveStreamController;
use App\Http\Controllers\Api\PastorUserController;

// ============================================
// ПРОКСИ МАРШРУТ - САМЫЙ ПЕРВЫЙ
// ============================================
Route::get('/doc-view/{path}', [DocumentProxyController::class, 'show'])
    ->where('path', '.*')
    ->name('doc.viewer');

// ============================================
// СОБЫТИЯ
// ============================================
Route::get('/events/carousel-stats', [EventController::class, 'carouselStats']);
Route::get('/events/upcoming', [EventController::class, 'upcoming']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{slug}', [EventController::class, 'show']);

// ============================================
// АВТОРИЗАЦИЯ
// ============================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

// ============================================
// КОНТАКТЫ (требуют авторизации)
// ============================================
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/contacts', [ContactsController::class, 'send']);
});

// Админские маршруты для работы с сообщениями (только для админов)
Route::middleware(['auth:sanctum', 'admin.access'])->prefix('admin')->group(function () {
    Route::get('/contacts', [ContactsController::class, 'index']);
    Route::get('/contacts/{id}', [ContactsController::class, 'show']);
    Route::put('/contacts/{id}/read', [ContactsController::class, 'markAsRead']);
    Route::delete('/contacts/{id}', [ContactsController::class, 'destroy']);
});

// ============================================
// CSRF ТОКЕН
// ============================================
Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

// ============================================
// LIVE STREAM
// ============================================
Route::get('/live/current', [LiveStreamController::class, 'current']);
Route::get('/live/upcoming', [LiveStreamController::class, 'upcoming']);

// ============================================
// ПОСТЫ И ФИЛЬТРЫ
// ============================================
Route::get('/posts/random', [PostController::class, 'recommended']);
Route::get('/filtered-categories', [PostController::class, 'filteredCategories']);
Route::get('/filtered-groups', [PostController::class, 'filteredGroups']);
Route::get('/filtered-conferences', [PostController::class, 'filteredConferences']);
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{slug}', [PostController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/groups', [GroupController::class, 'index']);
Route::get('/conferences', [ConferenceController::class, 'index']);

// ============================================
// БИБЛИЯ
// ============================================
Route::prefix('bible')->group(function () {
    Route::get('/verse-of-the-day', [BibleController::class, 'verseOfTheDay']);
    Route::get('/', [BibleController::class, 'index']);
    Route::get('/{slug}', [BibleController::class, 'show']);
    Route::post('/clear-cache', [BibleController::class, 'clearCache'])->middleware(['auth:sanctum', 'admin.access']);
});


// ============================================
// ВЕРИФИКАЦИЯ EMAIL
// ============================================
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
    
Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

// ============================================
// СТАТИСТИКА
// ============================================
Route::post('/posts/{postId}/view', [StatsController::class, 'trackView']);
Route::post('/posts/{postId}/like', [StatsController::class, 'toggleLike']);
Route::get('/posts/{postId}/stats', [StatsController::class, 'getStats']);

// ============================================
// ABOUT
// ============================================
Route::get('/abouts', [AboutController::class, 'index']);
Route::get('/abouts/{slug}', [AboutController::class, 'show']);
Route::get('/denominations', [AboutController::class, 'denominations']);
Route::get('/denominations/{slug}/abouts', [AboutController::class, 'byDenomination']);

// ============================================
// DENOMINATIONS
// ============================================
Route::get('/denominations', [DenominationController::class, 'index']);
Route::get('/denominations/{slug}', [DenominationController::class, 'show']);

// Маршруты для пастора (управление пользователями)
Route::middleware(['auth:sanctum'])->prefix('pastor')->group(function () {
    Route::get('/users', [PastorUserController::class, 'index']);
    Route::get('/users/export', [PastorUserController::class, 'export']);
    Route::get('/users/{userId}', [PastorUserController::class, 'show']);
    Route::put('/users/{userId}/roles', [PastorUserController::class, 'updateRoles']);
});

// ============================================
// ЗАЩИЩЕННЫЕ МАРШРУТЫ (ТРЕБУЕТСЯ АВТОРИЗАЦИЯ И ПОДТВЕРЖДЕНИЕ EMAIL)
// ============================================
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // События (CRUD)
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    
    // Профиль пользователя
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/avatar', [AvatarController::class, 'upload']);
    Route::delete('/user/avatar', [AvatarController::class, 'destroy']);
    Route::get('/user/avatar', [AvatarController::class, 'show']);
    Route::post('/user/consent', [AuthController::class, 'updateConsent']);
    Route::get('/user/consent/history', [AuthController::class, 'consentHistory']);
    
    // Избранное
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{postId}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites/check/{postId}', [FavoriteController::class, 'check']);
    
    // Загрузка файлов
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::delete('/upload', [UploadController::class, 'delete']);
});