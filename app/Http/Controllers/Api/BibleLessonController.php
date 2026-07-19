<?php
// app/Http/Controllers/Api/BibleLessonController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleLesson;
use App\Models\BibleUserLessonProgress;
use App\Models\BibleLessonComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;  // ✅ ПРАВИЛЬНОЕ МЕСТО — ВНЕ КЛАССА

class BibleLessonController extends Controller
{
    /**
     * Детальная информация об уроке
     */
    public function show($slug)
    {
        $lesson = BibleLesson::where('slug', $slug)
            ->where('is_published', true)
            ->with(['videos', 'course'])
            ->firstOrFail();

        $user = Auth::user();

        if (!$user || !$user->isEnrolledInSchool()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учеников школы'
            ], 403);
        }

        // Проверка блокировки урока
        if ($this->isLessonLocked($lesson, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Этот урок заблокирован. Пройдите предыдущий урок.',
                'is_locked' => true
            ], 403);
        }

        // Получаем прогресс пользователя
        $progress = BibleUserLessonProgress::firstOrCreate([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id
        ], [
            'status' => 'not_started'
        ]);

        // Для лидера группы проверяем посещаемость
        $isAttended = true;
        if ($user->isGroupLeader() && $progress->attended_by_leader_at === null) {
            $isAttended = false;
        }

        // Получаем комментарии к уроку
        $comments = $lesson->approvedComments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'user_id', 'content', 'created_at']);

        return response()->json([
            'success' => true,
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'course_slug' => $lesson->course->slug,
                'call_question' => $lesson->call_question,
                'call_answer' => $lesson->call_answer, 
                'scripture_verses' => $lesson->scripture_verses,
                'content' => $lesson->content,
                'practice_task' => $lesson->practice_task,
'videos' => $lesson->videos->map(fn($v) => [
                'id' => $v->id,
                'title' => $v->title,
                'embed_url' => $v->embed_url,
                'platform' => $v->platform,
                'order' => $v->order,
            ]),
                'pdf_conspect_url' => $lesson->pdf_conspect_url,
            ],
            'progress' => [
                'status' => $progress->status,
                'video_watched_at' => $progress->video_watched_at,
                'practice_completed_at' => $progress->practice_completed_at,
                'test_passed_at' => $progress->test_passed_at,
                'test_score' => $progress->test_score,
                'attended_by_leader_at' => $progress->attended_by_leader_at,
                'is_attended' => $isAttended
            ],
            'comments' => $comments,
            'is_locked' => false
        ]);
    }

    /**
     * Отметить прочтение Призыва
     */
    public function markCall(Request $request, $slug)
    {
        return $this->updateProgress($slug, 'markCallCompleted');
    }

    /**
     * Отметить прочтение Писания
     */
    public function markScripture(Request $request, $slug)
    {
        return $this->updateProgress($slug, 'markScriptureCompleted');
    }

    /**
     * Отметить просмотр видео
     */
    public function markVideoWatched(Request $request, $slug)
    {
        return $this->updateProgress($slug, 'markVideoWatched');
    }

    /**
     * Отметить выполнение практики
     */
    public function markPracticeCompleted(Request $request, $slug)
    {
        return $this->updateProgress($slug, 'markPracticeCompleted');
    }

    /**
     * Универсальный метод обновления прогресса
     */
    private function updateProgress($slug, string $method)
    {
        $lesson = BibleLesson::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $user = Auth::user();

        if (!$user || !$user->isEnrolledInSchool()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учеников школы'
            ], 403);
        }

        // Проверка блокировки
        if ($this->isLessonLocked($lesson, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Этот урок заблокирован'
            ], 403);
        }

        $progress = BibleUserLessonProgress::firstOrCreate([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id
        ]);

        $progress->$method();

        return response()->json([
            'success' => true,
            'message' => 'Прогресс обновлён',
            'status' => $progress->status
        ]);
    }

    /**
     * Скачать урок в PDF
     */
    public function downloadPdf($slug)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isEnrolledInSchool()) {
            abort(403, 'Доступ запрещён');
        }
        
        $lesson = BibleLesson::where('slug', $slug)->firstOrFail();
        
        // Заменяем относительные пути картинок на полные S3 URL
        $content = $lesson->content;
        $content = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/i', function($matches) {
            $src = $matches[1];
            
            // Если уже полный URL
            if (preg_match('/^https?:\/\//', $src)) {
                // Если это S3, оставляем как есть
                if (str_contains($src, 'storage.yandexcloud.net')) {
                    return $matches[0];
                }
                return $matches[0];
            }
            
            // Формируем полный S3 URL
            $cleanPath = ltrim($src, '/');
            $fullUrl = 'https://storage.yandexcloud.net/wotgospel-media/' . $cleanPath;
            
            return str_replace($matches[1], $fullUrl, $matches[0]);
        }, $content);
        
        $html = view('pdf.lesson', [
            'lesson' => $lesson,
            'user' => $user,
            'content' => $content
        ])->render();
        
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        
        // Включаем загрузку внешних изображений
        $pdf->getOptions()->set('isRemoteEnabled', true);
        $pdf->getOptions()->set('isHtml5ParserEnabled', true);
        
        return $pdf->download("lesson-{$lesson->slug}.pdf");
    }
    
    /**
     * Проверка блокировки урока
     */
    private function isLessonLocked($lesson, int $userId): bool
    {
        // Первый урок всегда открыт
        if ($lesson->order == 1) {
            return false;
        }
        
        $previousLesson = $lesson->getPreviousLesson();
        
        if (!$previousLesson) {
            return false;
        }
        
        // Получаем прогресс предыдущего урока
        $progress = BibleUserLessonProgress::where('user_id', $userId)
            ->where('lesson_id', $previousLesson->id)
            ->first();
        
        // Урок заблокирован, если нет прогресса или предыдущий урок не пройден
        if (!$progress) {
            return true;
        }
        
        // Проверяем статус предыдущего урока
        $isCompleted = ($progress->status === 'test_passed' || $progress->status === 'completed');
        
        return !$isCompleted;
    }
    
    /**
 * Получить следующий урок
 */
public function getNextLesson($slug)
{
    $user = Auth::user();
    
    if (!$user || !$user->isStudent()) {
        return response()->json(['success' => false, 'message' => 'Доступ запрещён'], 403);
    }
    
    $currentLesson = BibleLesson::where('slug', $slug)
        ->where('is_published', true)
        ->firstOrFail();
    
    $nextLesson = $currentLesson->getNextLesson();
    
    return response()->json([
        'success' => true,
        'next_lesson' => $nextLesson ? [
            'id' => $nextLesson->id,
            'title' => $nextLesson->title,
            'slug' => $nextLesson->slug,
        ] : null,
    ]);
}
}