<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleTestQuestion;
use App\Models\BibleLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherQuestionController extends Controller
{
    /**
     * Список всех вопросов
     */
    public function index(Request $request)
    {
        $query = BibleTestQuestion::with('lesson.course');
        
        if ($request->has('lesson_id')) {
            $query->where('lesson_id', $request->lesson_id);
        }
        
        if ($request->has('course_id')) {
            $query->whereHas('lesson', function($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }
        
        $questions = $query->orderBy('lesson_id')->orderBy('order')->get();
        return response()->json(['questions' => $questions]);
    }

    /**
     * Создание вопроса
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'lesson_id' => 'required|exists:bible_lessons,id',
            'theme_id' => 'nullable|exists:bible_themes,id',
            'type' => 'required|string|in:single_choice,multiple_choice,essay,case,true_false,fill_blank,matching,ordering,odd_one_out,verse_reference,select_verse',
            'question' => 'required|string',
            'config' => 'nullable|array',
            'points' => 'nullable|integer|min:1',
            'order' => 'nullable|integer',
        ]);

        $data['points'] = $data['points'] ?? 1;
        $data['order'] = $data['order'] ?? 0;
        
        if (isset($data['config'])) {
            $data['config'] = json_encode($data['config']);
        }

        $question = BibleTestQuestion::create($data);
        
        // Перезагружаем с отношениями
        $question->load('lesson.course');
        
        return response()->json(['question' => $question], 201);
    }

    /**
     * Получение одного вопроса
     */
    public function show($id)
    {
        $question = BibleTestQuestion::with('lesson.course')->findOrFail($id);
        
        // Декодируем config
        if ($question->config && is_string($question->config)) {
            $question->config = json_decode($question->config, true);
        }
        
        return response()->json(['question' => $question]);
    }

    /**
     * Обновление вопроса
     */
    public function update(Request $request, $id)
    {
        $question = BibleTestQuestion::findOrFail($id);

        $data = $request->validate([
            'lesson_id' => 'sometimes|exists:bible_lessons,id',
            'theme_id' => 'nullable|exists:bible_themes,id',
            'type' => 'sometimes|string|in:single_choice,multiple_choice,essay,case,true_false,fill_blank,matching,ordering,odd_one_out,verse_reference,select_verse',
            'question' => 'sometimes|string',
            'config' => 'nullable|array',
            'points' => 'nullable|integer|min:1',
            'order' => 'nullable|integer',
        ]);

        if (isset($data['config'])) {
            $data['config'] = json_encode($data['config']);
        }

        $question->update($data);
        
        // Декодируем config для ответа
        if ($question->config && is_string($question->config)) {
            $question->config = json_decode($question->config, true);
        }
        
        return response()->json(['question' => $question]);
    }

    /**
     * Удаление вопроса
     */
    public function destroy($id)
    {
        $question = BibleTestQuestion::findOrFail($id);
        
        // Проверяем, есть ли связанные эссе
        if ($question->essays()->count() > 0) {
            return response()->json([
                'message' => 'Невозможно удалить вопрос, есть связанные эссе.'
            ], 422);
        }
        
        $question->delete();
        return response()->json(['message' => 'Вопрос удалён']);
    }

    /**
     * Получение типов вопросов (для фронта)
     */
    public function getTypes()
    {
        $types = [
            ['value' => 'single_choice', 'label' => 'Одиночный выбор'],
            ['value' => 'multiple_choice', 'label' => 'Множественный выбор'],
            ['value' => 'essay', 'label' => 'Эссе'],
            ['value' => 'case', 'label' => 'Кейс'],
            ['value' => 'true_false', 'label' => 'Правда/Ложь'],
            ['value' => 'fill_blank', 'label' => 'Заполнить пропуски'],
            ['value' => 'matching', 'label' => 'Соответствие'],
            ['value' => 'ordering', 'label' => 'Порядок'],
            ['value' => 'odd_one_out', 'label' => 'Убрать лишнее'],
            ['value' => 'verse_reference', 'label' => 'Ссылка на стих'],
            ['value' => 'select_verse', 'label' => 'Выбор стиха'],
        ];
        
        return response()->json(['types' => $types]);
    }
}