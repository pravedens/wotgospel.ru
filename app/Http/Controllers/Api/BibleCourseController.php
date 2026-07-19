<?php
// app/Http/Controllers/Api/BibleCourseController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleCourse;
use App\Models\BibleLesson;
use App\Models\BibleUserLessonProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BibleCourseController extends Controller
{
    /**
     * Список всех опубликованных курсов
     */
    public function index(Request $request)
{
    $user = $request->user();
    
    $courses = BibleCourse::where('is_published', true)
        ->orderBy('order')
        ->with(['themes' => function($q) {
            $q->where('is_published', true)
              ->orderBy('order')
              ->with(['lessons' => function($q2) {
                  $q2->where('is_published', true)->orderBy('order');
              }]);
        }])
        ->get();
    
    $teachers = User::role('teacher')->get(['id', 'name', 'last_name', 'avatar']);
    
    $result = $courses->map(function ($course) use ($teachers, $user) {
        $progress = $user && $user->isStudent() ? $course->getProgressForUser($user->id) : null;
        
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'image_url' => $course->image_url,
            'progress' => $progress,  // ← добавляем прогресс
            'teachers' => $teachers->map(fn($t) => [
                'id' => $t->id,
                'full_name' => $t->full_name,
                'avatar_url' => $t->avatar_url,
            ]),
            'themes' => $course->themes->map(fn($theme) => [
                'id' => $theme->id,
                'title' => $theme->title,
                'description' => $theme->description,
                'lessons_count' => $theme->lessons->count(),
                'lessons' => $theme->lessons->map(fn($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                ]),
            ]),
        ];
    });
    
    return response()->json(['success' => true, 'courses' => $result]);
}

   /**
 * Детальная информация о курсе
 */
public function show($slug)
{
    $course = BibleCourse::where('slug', $slug)
        ->where('is_published', true)
        ->with(['themes' => function($q) {
            $q->where('is_published', true)
              ->orderBy('order')
              ->with(['lessons' => function($q2) {
                  $q2->where('is_published', true)->orderBy('order');
              }]);
        }])
        ->firstOrFail();

    $user = Auth::user();
    
    // ✅ Получаем уроки через темы, сохраняя структуру
    $themes = $course->themes->map(function($theme) use ($user) {
        $lessons = $theme->lessons->map(function($lesson) use ($user) {
            if ($user && $user->isEnrolledInSchool()) {
                $progress = BibleUserLessonProgress::where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->first();
                $lesson->status = $progress ? $progress->status : 'not_started';
                $lesson->is_locked = $this->isLessonLocked($lesson, $user->id);
            } else {
                $lesson->status = 'not_started';
                $lesson->is_locked = true;
            }
            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'order' => $lesson->order,
                'status' => $lesson->status,
                'is_locked' => $lesson->is_locked,
                'call_question' => $lesson->call_question,
            ];
        });
        
        return [
            'id' => $theme->id,
            'title' => $theme->title,
            'description' => $theme->description,
            'lessons' => $lessons,
        ];
    });
    
    $response = $course->toArray();
    $response['themes'] = $themes;
    unset($response['lessons']); // Удаляем плоскую структуру уроков
    
    if ($user && $user->isEnrolledInSchool()) {
        $response['progress'] = $course->getProgressForUser($user->id);
    }

    return response()->json([
        'success' => true,
        'course' => $response
    ]);
}

    /**
     * Проверка, заблокирован ли урок для пользователя
     */
    public function isLessonLocked($lesson, int $userId): bool
{
    // Первый урок курса (самый маленький order) всегда открыт
    $firstLesson = BibleLesson::where('course_id', $lesson->course_id)
        ->where('is_published', true)
        ->orderBy('order', 'asc')
        ->first();
    
    if ($firstLesson && $firstLesson->id === $lesson->id) {
        return false;
    }
    
    // Находим предыдущий урок по order (глобально по курсу)
    $previousLesson = BibleLesson::where('course_id', $lesson->course_id)
        ->where('order', '<', $lesson->order)
        ->where('is_published', true)
        ->orderBy('order', 'desc')
        ->first();
    
    if (!$previousLesson) {
        return false;
    }
    
    $progress = BibleUserLessonProgress::where('user_id', $userId)
        ->where('lesson_id', $previousLesson->id)
        ->first();
    
    if (!$progress) {
        return true;
    }
    
    $isCompleted = ($progress->status === 'completed' || $progress->status === 'test_passed');
    
    return !$isCompleted;
}
    
    /**
 * Список учителей
 */
public function teachers()
{
    $teachers = User::role('teacher')
        ->select(['id', 'name', 'last_name', 'avatar', 'about'])
        ->get()
        ->map(function ($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'last_name' => $teacher->last_name,
                'full_name' => $teacher->full_name,
                'avatar_url' => $teacher->avatar_url,
                'about' => $teacher->about,
            ];
        });
    
    return response()->json([
        'success' => true,
        'teachers' => $teachers
    ]);
}

/**
 * Публичный просмотр курса (для гостей) — обзорная программа
 */
public function showPublic($slug)
{
    $course = BibleCourse::where('slug', $slug)
        ->where('is_published', true)
        ->with(['themes' => function($q) {
            $q->where('is_published', true)
              ->orderBy('order')
              ->with(['teacher:id,name,last_name,avatar,about']);
        }])
        ->firstOrFail();
    
    // Уроки без ссылок
    $lessons = $course->lessons()
        ->where('is_published', true)
        ->orderBy('order')
        ->get(['id', 'title', 'order']);
    
    // Преподаватели курса
    $teachers = User::role('teacher')
        ->select(['id', 'name', 'last_name', 'avatar', 'about'])
        ->get()
        ->map(fn($t) => [
            'id' => $t->id,
            'full_name' => $t->full_name,
            'avatar_url' => $t->avatar_url,
            'about' => $t->about,
        ]);
    
    // Статусы обучения
    $statuses = $course->statuses_list;
    
    return response()->json([
        'success' => true,
        'course' => [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'what_you_will_learn' => $course->what_you_will_learn,
            'skills' => $course->skills,
            'price' => $course->price,
            'certificate_text' => $course->certificate_text,
            'image_url' => $course->image_url,
        ],
        'lessons' => $lessons->map(fn($l) => [
            'order' => $l->order,
            'title' => $l->title,
        ]),
        'teachers' => $teachers,
        'themes' => $course->themes->map(fn($theme) => [
            'id' => $theme->id,
            'title' => $theme->title,
            'description' => $theme->description,
            'teacher' => $theme->teacher ? [
                'id' => $theme->teacher->id,
                'full_name' => $theme->teacher->full_name,
                'avatar_url' => $theme->teacher->avatar_url,
            ] : null,
            'lessons_count' => $theme->lessons->count(),
            'lessons' => $theme->lessons->map(fn($lesson) => [
                'title' => $lesson->title,
            ]),
        ]),
        'statuses' => $statuses,
    ]);
}
}