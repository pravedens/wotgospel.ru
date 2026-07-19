<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleTheme;
use App\Models\BibleCourse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TeacherThemeController extends Controller
{
    /**
     * Список всех тем
     */
    public function index()
    {
        $themes = BibleTheme::with('course', 'teacher')->orderBy('course_id')->orderBy('order')->get();
        return response()->json(['themes' => $themes]);
    }

    /**
     * Создание темы
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:bible_courses,id',
            'teacher_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_published' => 'sometimes|boolean',
        ]);

        $data['slug'] = Str::slug($data['title']);
        
        // Проверка уникальности slug в рамках курса
        $existing = BibleTheme::where('course_id', $data['course_id'])
            ->where('slug', $data['slug'])
            ->first();
        if ($existing) {
            $data['slug'] = $data['slug'] . '-' . time();
        }

        $theme = BibleTheme::create($data);
        return response()->json(['theme' => $theme], 201);
    }

    /**
     * Получение одной темы
     */
    public function show($id)
    {
        $theme = BibleTheme::with(['course', 'teacher', 'lessons'])->findOrFail($id);
        return response()->json(['theme' => $theme]);
    }

    /**
     * Обновление темы
     */
    public function update(Request $request, $id)
    {
        $theme = BibleTheme::findOrFail($id);

        $data = $request->validate([
            'course_id' => 'sometimes|exists:bible_courses,id',
            'teacher_id' => 'nullable|exists:users,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_published' => 'sometimes|boolean',
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
            // Проверка уникальности slug
            $existing = BibleTheme::where('course_id', $data['course_id'] ?? $theme->course_id)
                ->where('slug', $data['slug'])
                ->where('id', '!=', $id)
                ->first();
            if ($existing) {
                $data['slug'] = $data['slug'] . '-' . time();
            }
        }

        $theme->update($data);
        return response()->json(['theme' => $theme]);
    }

    /**
     * Удаление темы
     */
    public function destroy($id)
    {
        $theme = BibleTheme::findOrFail($id);
        
        // Проверяем, есть ли уроки в этой теме
        if ($theme->lessons()->count() > 0) {
            return response()->json([
                'message' => 'Невозможно удалить тему, в ней есть уроки. Сначала удалите или переместите уроки.'
            ], 422);
        }
        
        $theme->delete();
        return response()->json(['message' => 'Тема удалена']);
    }

    /**
     * Включить/выключить публикацию
     */
    public function togglePublish($id)
    {
        $theme = BibleTheme::findOrFail($id);
        $theme->is_published = !$theme->is_published;
        $theme->save();
        return response()->json(['theme' => $theme]);
    }
}