<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// ============================================
// ИМПОРТЫ КОНТРОЛЛЕРОВ
// ============================================
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
use App\Http\Controllers\Api\NotificationSettingsController;
use App\Http\Controllers\Api\TestNotificationController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\EventRegistrationController;
use App\Http\Controllers\Api\MinisterController;
use App\Http\Controllers\Api\MinisterMessageController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\BibleCourseController;
use App\Http\Controllers\Api\BibleCertificateController;
use App\Http\Controllers\Api\BibleEnrollmentController;
use App\Http\Controllers\Api\BibleProgressController;
use App\Http\Controllers\Api\BibleLessonController;
use App\Http\Controllers\Api\BibleTestController;
use App\Http\Controllers\Api\BibleEssayController;
use App\Http\Controllers\Api\BibleCommentController;
use App\Http\Controllers\Api\BiblePartyController;
use App\Http\Controllers\Api\BibleSchoolController;
use App\Http\Controllers\Api\BibleTeacherController;
use App\Http\Controllers\Api\TeacherMessageController;
use App\Http\Controllers\Api\TeacherLessonController;
use App\Http\Controllers\Api\TeacherCourseController;
use App\Http\Controllers\Api\TeacherThemeController;
use App\Http\Controllers\Api\TeacherQuestionController;
use App\Http\Controllers\Api\BibleThemeController;
use App\Http\Controllers\Api\ChatController;

// ============================================
// ПРОКСИ ДЛЯ ПРОСМОТРА ДОКУМЕНТОВ
// ============================================
Route::get('/doc-view/{path}', [DocumentProxyController::class, 'show'])
    ->where('path', '.*')
    ->name('doc.viewer');
    
// ============================================
// BROADCASTING AUTH (СТАНДАРТНЫЙ LARAVEL)
// ============================================
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

// ============================================
// ПУБЛИЧНЫЕ МАРШРУТЫ
// ============================================
Route::get('/events/carousel-stats', [EventController::class, 'carouselStats']);
Route::get('/events/upcoming', [EventController::class, 'upcoming']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{slug}', [EventController::class, 'show']);

Route::get('/live/current', [LiveStreamController::class, 'current']);
Route::get('/live/upcoming', [LiveStreamController::class, 'upcoming']);

Route::get('/posts/random', [PostController::class, 'recommended']);
Route::get('/filtered-categories', [PostController::class, 'filteredCategories']);
Route::get('/filtered-groups', [PostController::class, 'filteredGroups']);
Route::get('/filtered-conferences', [PostController::class, 'filteredConferences']);
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{slug}', [PostController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/groups', [GroupController::class, 'index']);
Route::get('/conferences', [ConferenceController::class, 'index']);

Route::get('/friends', [FriendController::class, 'index']);
Route::get('/friends/{slug}', [FriendController::class, 'show']);

// ============================================
// КОММЕНТАРИИ К ПРОПОВЕДЯМ
// ============================================
Route::get('/posts/{postId}/comments', [CommentController::class, 'index']);
Route::post('/posts/{postId}/comments', [CommentController::class, 'store'])->middleware('auth:sanctum');
Route::delete('/comments/{commentId}', [CommentController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/comments/{commentId}/like', [CommentController::class, 'toggleLike'])->middleware('auth:sanctum');

Route::get('/abouts', [AboutController::class, 'index']);
Route::get('/abouts/{slug}', [AboutController::class, 'show']);
Route::get('/denominations', [AboutController::class, 'denominations']);
Route::get('/denominations/{slug}/abouts', [AboutController::class, 'byDenomination']);
Route::get('/denominations', [DenominationController::class, 'index']);
Route::get('/denominations/{slug}', [DenominationController::class, 'show']);

// ============================================
// ПУБЛИЧНЫЕ МАРШРУТЫ ДЛЯ СЛУЖИТЕЛЕЙ
// ============================================
Route::get('/ministers/categories', [MinisterController::class, 'categories']);
Route::get('/ministers/category/{slug}', [MinisterController::class, 'byCategory']);
Route::get('/ministers', [MinisterController::class, 'index']);
Route::get('/ministers/{id}', [MinisterController::class, 'show']);
Route::post('/ministers/{id}/message', [MinisterMessageController::class, 'send']);

// ============================================
// БИБЛИЯ (стихи дня)
// ============================================
Route::prefix('bible')->group(function () {
    Route::get('/verse-of-the-day', [BibleController::class, 'verseOfTheDay']);
    Route::get('/', [BibleController::class, 'index']);
    Route::get('/{slug}', [BibleController::class, 'show']);
    Route::post('/clear-cache', [BibleController::class, 'clearCache'])
        ->middleware(['auth:sanctum', 'admin.access']);
});

// ============================================
// АВТОРИЗАЦИЯ
// ============================================
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

// ============================================
// EMAIL ВЕРИФИКАЦИЯ
// ============================================
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

// ============================================
// CSRF И ПРОЧЕЕ
// ============================================
Route::get('/csrf-token', fn() => response()->json(['csrf_token' => csrf_token()]));
Route::get('/contacts/recipients-public', [ContactsController::class, 'getPublicRecipients']);
Route::get('/events/{event}/attendees-count', [EventController::class, 'getAttendeesCount']);

// ============================================
// ЗАЩИЩЁННЫЕ МАРШРУТЫ (auth:sanctum)
// ============================================
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/contacts/recipients', [ContactsController::class, 'getRecipients']);
    Route::post('/contacts', [ContactsController::class, 'send']);

    Route::get('/user/notification-settings', [NotificationSettingsController::class, 'getSettings']);
    Route::put('/user/notification-settings', [NotificationSettingsController::class, 'updateSettings']);
    Route::post('/user/test-notification', [TestNotificationController::class, 'sendTest']);
    Route::post('/push-subscription', [PushSubscriptionController::class, 'store']);
    Route::delete('/push-subscription', [PushSubscriptionController::class, 'destroy']);

    Route::prefix('pastor')->group(function () {
        Route::get('/users', [PastorUserController::class, 'index']);
        Route::get('/users/export', [PastorUserController::class, 'export']);
        Route::get('/users/{userId}', [PastorUserController::class, 'show']);
        Route::put('/users/{userId}/roles', [PastorUserController::class, 'updateRoles']);
    });

    Route::get('/user/social-links', [AuthController::class, 'getSocialLinks']);
    Route::put('/user/social-links', [AuthController::class, 'updateSocialLinks']);
    Route::get('/user/field-visibilities', [AuthController::class, 'getFieldVisibilities']);
    Route::put('/user/field-visibilities', [AuthController::class, 'updateFieldVisibilities']);
    Route::get('/user/minister-categories', [AuthController::class, 'getMinisterCategories']);
    Route::put('/user/minister-categories', [AuthController::class, 'updateMinisterCategories']);
    Route::get('/user/check-token', [AuthController::class, 'checkToken']);

    Route::get('/my-messages', [MinisterMessageController::class, 'getMessages']);
    Route::put('/my-messages/{id}/read', [MinisterMessageController::class, 'markAsRead']);
    Route::get('/my-messages/unread-count', [MinisterMessageController::class, 'getUnreadCount']);
    Route::delete('/my-messages/{id}', [MinisterMessageController::class, 'destroy']);

    Route::get('/user/minister-notification-settings', [AuthController::class, 'getMinisterNotificationSettings']);
    Route::put('/user/minister-notification-settings', [AuthController::class, 'updateMinisterNotificationSettings']);

    Route::post('/events/{slug}/attend', [EventController::class, 'attend']);
    Route::delete('/events/{slug}/attend', [EventController::class, 'attend']);
    Route::get('/events/{slug}/attendees-count', [EventController::class, 'getAttendeesCount']);

    Route::middleware('admin.access')->prefix('admin')->group(function () {
        Route::get('/contacts', [ContactsController::class, 'index']);
        Route::get('/contacts/{id}', [ContactsController::class, 'show']);
        Route::put('/contacts/{id}/read', [ContactsController::class, 'markAsRead']);
        Route::delete('/contacts/{id}', [ContactsController::class, 'destroy']);
    });

    Route::middleware('verified')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);
        Route::post('/user/consent', [AuthController::class, 'updateConsent']);
        Route::get('/user/consent/history', [AuthController::class, 'consentHistory']);

        Route::post('/user/avatar', [AvatarController::class, 'upload']);
        Route::delete('/user/avatar', [AvatarController::class, 'destroy']);
        Route::get('/user/avatar', [AvatarController::class, 'show']);

        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites', [FavoriteController::class, 'store']);
        Route::delete('/favorites/{postId}', [FavoriteController::class, 'destroy']);
        Route::get('/favorites/check/{postId}', [FavoriteController::class, 'check']);

        Route::post('/upload', [UploadController::class, 'upload']);
        Route::delete('/upload', [UploadController::class, 'delete']);

        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);

        Route::post('/events/{event}/register', [EventRegistrationController::class, 'register']);
        Route::get('/events/{event}/my-registration', [EventRegistrationController::class, 'getUserRegistration']);
        Route::get('/user/registrations', [EventRegistrationController::class, 'userRegistrations']);
        Route::put('/registrations/{registration}/cancel', [EventRegistrationController::class, 'cancel']);
    });
});

Route::post('/posts/{postId}/view', [StatsController::class, 'trackView']);
Route::post('/posts/{postId}/like', [StatsController::class, 'toggleLike']);
Route::get('/posts/{postId}/stats', [StatsController::class, 'getStats']);

Route::any('/any-request', function(Request $request) {
    \Log::info('ANY REQUEST HIT', [
        'method' => $request->method(),
        'uri' => $request->path(),
        'full_url' => $request->fullUrl()
    ]);
    return response()->json(['status' => 'logged']);
});

// ============================================
// ВРЕМЕННЫЙ МАРШРУТ ДЛЯ ЭССЕ (без параметра в URL)
// ============================================
Route::post('/bible-school/essay-store', [BibleEssayController::class, 'storeTemp'])
    ->middleware('auth:sanctum');
    
// ============================================
// ПОИСК ПОЛЬЗОВАТЕЛЕЙ ДЛЯ ЧАТА
// ============================================
Route::middleware(['auth:sanctum'])->get('/users/search', [ChatController::class, 'searchUsers']);

// ============================================
// ОНЛАЙН-БИБЛЕЙСКАЯ ШКОЛА
// ============================================
Route::prefix('bible-school')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/enroll', [BibleEnrollmentController::class, 'store']);
        Route::post('/enroll/unblock/{userId}', [BibleEnrollmentController::class, 'unblock']);
    });

    // ========== ПУБЛИЧНЫЕ (гость) ==========
    Route::get('/page-data', [BibleSchoolController::class, 'index']);

    Route::get('/courses', [BibleCourseController::class, 'index']);
    Route::get('/certificate/verify/{uuid}', [BibleCertificateController::class, 'verify']);
    Route::get('/courses/{course:slug}/preview', [BibleCourseController::class, 'showPublic']);
    Route::get('/teachers', [BibleCourseController::class, 'teachers']);
    Route::post('/teachers/{id}/message', [TeacherMessageController::class, 'send']);
    Route::get('/graduates', [BibleSchoolController::class, 'graduates']);
    Route::post('/bible-school/essay-store', [BibleEssayController::class, 'storeTemp'])->middleware('auth:sanctum');

    // ========== УЧИТЕЛЬ (общая группа) ==========
    Route::middleware(['auth:sanctum', 'role.teacher'])->group(function () {
        
        // Существующие методы BibleTeacherController
        Route::get('/teacher/dashboard', [BibleTeacherController::class, 'dashboard']);
        Route::post('/enrollment-requests/{id}/approve', [BibleTeacherController::class, 'approveRequest']);
        Route::post('/enrollment-requests/{id}/reject', [BibleTeacherController::class, 'rejectRequest']);
        Route::put('/teacher/students/{userId}/role', [BibleTeacherController::class, 'updateStudentRole']);
        Route::post('/essays/{essayId}/review', [BibleTeacherController::class, 'reviewEssay']);
        Route::post('/teacher/students/{studentId}/message', [BibleTeacherController::class, 'sendMessageToStudent']);
        Route::get('/teacher-messages', [TeacherMessageController::class, 'getMessages']);
        Route::put('/teacher-messages/{id}/read', [TeacherMessageController::class, 'markAsRead']);
        Route::get('/teacher-messages/unread-count', [TeacherMessageController::class, 'getUnreadCount']);
        Route::delete('/teacher-messages/{id}', [TeacherMessageController::class, 'destroy']);
        
        // Дополнительные методы для учителя (студенты)
        Route::get('/teacher/students', [BibleTeacherController::class, 'getStudents']);
        Route::delete('/teacher/students/{userId}/role', [BibleTeacherController::class, 'removeStudentRole']);
        Route::put('/teacher/students/{userId}/course', [BibleTeacherController::class, 'updateStudentCourse']);
        Route::delete('/teacher/students/{userId}/role', [BibleTeacherController::class, 'removeStudentRole']);
        
        // Темы для учителя (публичные? лучше оставить в общей группе для всех)
        Route::get('/themes', [BibleThemeController::class, 'indexAll']);
        Route::get('/themes/{course:slug}', [BibleThemeController::class, 'index']);
        Route::get('/themes/{theme:slug}/lessons', [BibleThemeController::class, 'lessons']);
        
        // ========== УПРАВЛЕНИЕ КУРСАМИ, УРОКАМИ, ТЕМАМИ, ВОПРОСАМИ ==========
        // Курсы
        Route::apiResource('/teacher/courses', TeacherCourseController::class);
        Route::patch('/teacher/courses/{id}/toggle-publish', [TeacherCourseController::class, 'togglePublish']);
        
        // Уроки
        Route::apiResource('/teacher/lessons', TeacherLessonController::class);
        Route::patch('/teacher/lessons/{id}/toggle-publish', [TeacherLessonController::class, 'togglePublish']);
        
        // Темы
        Route::apiResource('/teacher/themes', TeacherThemeController::class);
        Route::patch('/teacher/themes/{id}/toggle-publish', [TeacherThemeController::class, 'togglePublish']);
        
        // Вопросы
        Route::apiResource('/teacher/questions', TeacherQuestionController::class);
        Route::get('/teacher/question-types', [TeacherQuestionController::class, 'getTypes']);
    });

    // ========== УЧЕНИК / ЛИДЕР ГРУППЫ ==========
    Route::middleware(['auth:sanctum', 'role.student'])->group(function () {
        
        Route::get('/courses/{course:slug}', [BibleCourseController::class, 'show']);

        // Заявки на обучение
        Route::get('/enroll/status', [BibleEnrollmentController::class, 'status']);

        // Прогресс
        Route::get('/my/progress', [BibleProgressController::class, 'index']);
        Route::get('/my/progress/{course:slug}', [BibleProgressController::class, 'courseProgress']);
        Route::post('/courses/{course:slug}/reset', [BibleProgressController::class, 'resetCourse']);

        // Уроки
        Route::get('/lessons/{lesson:slug}', [BibleLessonController::class, 'show']);
        Route::post('/lessons/{lesson:slug}/call', [BibleLessonController::class, 'markCall']);
        Route::post('/lessons/{lesson:slug}/scripture', [BibleLessonController::class, 'markScripture']);
        Route::post('/lessons/{lesson:slug}/video-watch', [BibleLessonController::class, 'markVideoWatched']);
        Route::post('/lessons/{lesson:slug}/practice', [BibleLessonController::class, 'markPracticeCompleted']);
        Route::get('/lessons/{lesson:slug}/download', [BibleLessonController::class, 'downloadPdf']);

        // Тесты
        Route::get('/lessons/{lesson:slug}/test', [BibleTestController::class, 'show']);
        Route::post('/lessons/{lesson:slug}/test', [BibleTestController::class, 'submit']);

        // Эссе
        Route::get('/my/essays', [BibleEssayController::class, 'index']);
        Route::get('/my/essays/{id}', [BibleEssayController::class, 'show']);
        Route::post('/lessons/{lesson:slug}/essay', [BibleEssayController::class, 'store']);
        Route::get('/lessons/{lesson}/next', [BibleLessonController::class, 'getNextLesson']);

        // Комментарии
        Route::get('/lessons/{lesson:slug}/comments', [BibleCommentController::class, 'index']);
        Route::post('/lessons/{lesson:slug}/comments', [BibleCommentController::class, 'store']);

        // Группы (Party)
        Route::post('/party/join', [BiblePartyController::class, 'join']);
        Route::delete('/party/leave', [BiblePartyController::class, 'leave']);
        Route::get('/party/my', [BiblePartyController::class, 'myParty']);
        Route::get('/party/messages', [BiblePartyController::class, 'getMessages']);
        Route::post('/party/messages', [BiblePartyController::class, 'sendMessage']);
        Route::delete('/party/messages/{id}', [BiblePartyController::class, 'deleteMessage']);
        Route::delete('/party/messages/{id}/self', [BiblePartyController::class, 'deleteOwnMessage']);
        Route::delete('/party/students/{userId}', [BiblePartyController::class, 'removeStudent']);

        // Видеоконференция (Jitsi)
        Route::post('/party/video-room', [BiblePartyController::class, 'createVideoRoom']);
        Route::get('/party/video-room/{roomId}', [BiblePartyController::class, 'getVideoRoom']);

        // Сертификаты
        Route::get('/my/certificates', [BibleCertificateController::class, 'index']);
        Route::get('/certificate/{uuid}/download', [BibleCertificateController::class, 'download']);
    });
    
    // ============================================
    // ЧАТ (ЕДИНАЯ СИСТЕМА)
    // ============================================
    Route::middleware(['auth:sanctum'])->prefix('chat')->group(function () {
        // Беседы
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::post('/conversations/find-or-create', [ChatController::class, 'findOrCreate']);
        Route::put('/conversations/{id}/read', [ChatController::class, 'markAsRead']);

        // Сообщения
        Route::get('/conversations/{id}/messages', [ChatController::class, 'getMessages']);
        Route::post('/send', [ChatController::class, 'sendMessage']);

        // Счётчики
        Route::get('/unread-count', [ChatController::class, 'getUnreadCount']);

        // Тайпинг (печатает)
        Route::post('/conversations/{id}/typing/start', [ChatController::class, 'typingStarted']);
        Route::post('/conversations/{id}/typing/stop', [ChatController::class, 'typingStopped']);
        
        Route::get('/teachers', [ChatController::class, 'getTeachers']);
    });
});