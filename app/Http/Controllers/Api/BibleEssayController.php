<?php
// app/Http/Controllers/Api/BibleEssayController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleEssay;
use App\Models\BibleLesson;
use App\Models\BibleTestQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BibleEssayController extends Controller
{
    /**
     * Список эссе текущего пользователя
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учеников'
            ], 403);
        }

        $essays = BibleEssay::where('user_id', $user->id)
            ->with(['lesson', 'question', 'user', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'essays' => $essays
        ]);
    }

    /**
     * Получить конкретное эссе
     */
    public function show($id)
    {
        $user = Auth::user();

        $essay = BibleEssay::with(['user', 'lesson', 'question', 'reviewer', 'teacher'])
            ->findOrFail($id);

        if ($essay->user_id !== $user->id && !$user->isTeacher() && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа к этому эссе'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'essay' => $essay
        ]);
    }

    /**
     * Создать или обновить эссе
     */
    public function store(Request $request, $lessonSlug)
    {
        $lesson = BibleLesson::where('slug', $lessonSlug)->firstOrFail();
        $user = Auth::user();

        if (!$user || !$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учеников'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|min:100',
            'teacher_id' => 'required|exists:users,id'
        ]);

        $teacher = User::find($request->teacher_id);
        
        if (!$teacher || !$teacher->hasRole('teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Выбранный пользователь не является учителем'
            ], 422);
        }

        $essay = BibleEssay::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'teacher_id' => $request->teacher_id,
            'content' => $request->content,
            'status' => 'pending',
            'question_id' => null
        ]);

        // ✅ Отправка уведомлений учителю
        $this->sendNotificationsToTeacher($teacher, $user, $lesson, $essay);

        return response()->json([
            'success' => true,
            'message' => 'Эссе отправлено на проверку',
            'essay' => $essay
        ]);
    }

    public function storeTemp(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || !$user->isStudent()) {
                return response()->json(['success' => false, 'message' => 'Доступ только для учеников'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'lesson_slug' => 'required|exists:bible_lessons,slug',
                'content' => 'required|string|min:100',
                'teacher_id' => 'required|exists:users,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Ошибка валидации', 'errors' => $validator->errors()], 422);
            }
            
            $lesson = BibleLesson::where('slug', $request->lesson_slug)->first();
            $teacher = User::find($request->teacher_id);
            
            if (!$teacher || !$teacher->hasRole('teacher')) {
                return response()->json(['success' => false, 'message' => 'Выбранный пользователь не является учителем'], 422);
            }
            
            $essay = BibleEssay::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'teacher_id' => $request->teacher_id,
                'content' => $request->content,
                'status' => 'pending',
                'question_id' => null
            ]);
            
            // ✅ Отправка уведомлений учителю
            $this->sendNotificationsToTeacher($teacher, $user, $lesson, $essay);
            
            return response()->json(['success' => true, 'message' => 'Эссе отправлено на проверку', 'essay' => $essay]);
            
        } catch (\Exception $e) {
            \Log::error('Essay store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Отправить уведомления учителю о новом эссе
     */
    private function sendNotificationsToTeacher(User $teacher, User $student, BibleLesson $lesson, BibleEssay $essay): void
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            
            // Email уведомление
            if ($teacher->notify_teacher_messages_email) {
                $notificationService->sendTeacherEssayNotification($teacher, $student, $lesson, $essay);
            }
            
            // WebPush уведомление
            if ($teacher->notify_teacher_messages_webpush) {
                $notificationService->sendTeacherEssayWebPush($teacher, $student, $lesson);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to send teacher essay notification: ' . $e->getMessage());
        }
    }
}