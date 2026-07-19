<?php
// app/Http/Controllers/Api/BibleTestController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleEssay;
use App\Models\BibleLesson;
use App\Models\BibleTestQuestion;
use App\Models\BibleUserLessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BibleTestController extends Controller
{
    /**
     * Получить тест для урока
     */
    public function show($lessonSlug)
{
    $lesson = BibleLesson::where('slug', $lessonSlug)
        ->where('is_published', true)
        ->firstOrFail();

    $user = Auth::user();

    if (! $user || ! $user->isStudent()) {
        return response()->json([
            'success' => false,
            'message' => 'Доступ только для учеников',
        ], 403);
    }

    $progress = BibleUserLessonProgress::where('user_id', $user->id)
        ->where('lesson_id', $lesson->id)
        ->first();

    // Тест доступен только после практики
    if (! $progress || $progress->status !== 'practice_completed') {
        return response()->json([
            'success' => false,
            'message' => 'Сначала выполните практическое задание',
        ], 403);
    }

    $questions = $lesson->questions()
        ->orderBy('order')
        ->get(['id', 'type', 'question', 'config', 'points', 'order']);

    foreach ($questions as $question) {
        // ✅ Декодируем config из JSON строки в массив
        $config = is_string($question->config) 
            ? json_decode($question->config, true) 
            : ($question->config ?? []);
        
        $question->config = $this->sanitizeQuestionConfigForStudent(
            $question->type,
            $config
        );
    }

    return response()->json([
        'success' => true,
        'lesson_id' => $lesson->id,
        'lesson_title' => $lesson->title,
        'questions' => $questions,
    ]);
}

    /**
     * Отправить ответы на тест
     */
    public function submit(Request $request, $lessonSlug)
    {
        $lesson = BibleLesson::where('slug', $lessonSlug)
            ->where('is_published', true)
            ->firstOrFail();

        $user = Auth::user();

        if (! $user || ! $user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учеников',
            ], 403);
        }

        $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'distinct', 'exists:bible_test_questions,id'],
            'answers.*.value' => ['required'],
        ]);

        $progress = BibleUserLessonProgress::firstOrCreate([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        // Проверяем, что практика выполнена
        if ($progress->status !== 'practice_completed') {
            return response()->json([
                'success' => false,
                'message' => 'Сначала выполните практическое задание',
            ], 403);
        }

        $lessonQuestions = BibleTestQuestion::where('lesson_id', $lesson->id)
            ->orderBy('order')
            ->get()
            ->keyBy('id');

        if ($lessonQuestions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'В этом уроке нет вопросов теста',
            ], 422);
        }

        $submittedAnswers = collect($request->input('answers'))
            ->keyBy('question_id');

        /*
         * Защита:
         * нельзя отправить question_id от другого урока.
         */
        $invalidQuestionIds = $submittedAnswers
            ->keys()
            ->diff($lessonQuestions->keys());

        if ($invalidQuestionIds->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'В ответах есть вопросы, которые не принадлежат этому уроку',
                'invalid_question_ids' => $invalidQuestionIds->values(),
            ], 422);
        }

        /*
         * Защита:
         * нельзя отправить только один правильный ответ и получить 100%.
         */
        $missingQuestionIds = $lessonQuestions
            ->keys()
            ->diff($submittedAnswers->keys());

        if ($missingQuestionIds->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Ответьте на все вопросы теста',
                'missing_question_ids' => $missingQuestionIds->values(),
            ], 422);
        }

        $totalPoints = (int) $lessonQuestions->sum('points');
        $earnedPoints = 0;
        $results = [];
        $createdEssayIds = [];

        DB::beginTransaction();

        try {
            foreach ($lessonQuestions as $question) {
    
                $answer = $submittedAnswers->get($question->id);
                $value = $answer['value'] ?? null;

                // Для эссе и case вопросов — сохраняем отдельно
                if (in_array($question->type, ['essay', 'case'], true)) {
                    $essay = BibleEssay::create([
                        'user_id' => $user->id,
                        'lesson_id' => $lesson->id,
                        'question_id' => $question->id,
                        'content' => $value,
                        'status' => 'pending',
                    ]);

                    $createdEssayIds[] = $essay->id;

                    $results[] = [
                        'question_id' => $question->id,
                        'correct' => null,
                        'earned' => 0,
                        'is_pending' => true,
                        'feedback' => 'Ответ отправлен на проверку учителю',
                        'essay_id' => $essay->id,
                    ];

                    continue;
                }

                $validation = $question->validateAnswer($value);
                $earnedPoints += (int) $validation['score'];

                $results[] = [
                    'question_id' => $question->id,
                    'correct' => $validation['correct'],
                    'earned' => $validation['score'],
                    'is_pending' => false,
                    'feedback' => $validation['feedback'],
                ];
            }

            $hasPendingEssays = count($createdEssayIds) > 0;

            if (! $hasPendingEssays) {
                // Все вопросы проверены автоматически
                $percentage = $totalPoints > 0
                    ? round(($earnedPoints / $totalPoints) * 100)
                    : 100;

                $isPassed = $percentage >= 70;

                if ($isPassed) {
                    $progress->markTestPassed($percentage);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'total_points' => $totalPoints,
                    'earned_points' => $earnedPoints,
                    'percentage' => $percentage,
                    'is_passed' => $isPassed,
                    'has_pending_essays' => false,
                    'results' => $results,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'total_points' => $totalPoints,
                'earned_points' => $earnedPoints,
                'has_pending_essays' => true,
                'message' => 'Тест сохранён. Ожидайте проверки эссе от учителя.',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении теста: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удаляет из config всё, что может подсказать правильный ответ ученику.
     */
    private function sanitizeQuestionConfigForStudent(string $type, array $config): array
    {
        unset(
            $config['correct'],
            $config['correct_odd'],
            $config['matches'],
            $config['answers'],
            $config['expected_book'],
            $config['expected_chapter'],
            $config['expected_verse'],
            $config['explanation']
        );

        if ($type === 'ordering') {
            $config['items'] = collect($config['items'] ?? [])
                ->map(fn ($item) => [
                    'text' => $item['text'] ?? '',
                ])
                ->shuffle()
                ->values()
                ->toArray();
        }

        return $config;
    }
}
