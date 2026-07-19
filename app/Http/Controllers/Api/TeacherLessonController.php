<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleLesson;
use App\Models\BibleCourse;
use App\Models\BibleTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TeacherLessonController extends Controller
{
    public function index()
{
    $lessons = BibleLesson::with('course', 'theme')->orderBy('course_id')->orderBy('order')->get();
    return response()->json(['lessons' => $lessons]);
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:bible_courses,id',
            'theme_id' => 'nullable|exists:bible_themes,id',
            'title' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'call_question' => 'nullable|string',
            'call_answer' => 'nullable|string',
            'scripture_verses' => 'nullable|string',
            'content' => 'nullable|string',
            'practice_task' => 'nullable|string',
            'video_url' => 'nullable|url',
            'pdf_conspect_url' => 'nullable|url',
            'is_published' => 'sometimes|boolean',
        ]);

        $data['slug'] = Str::slug($data['title']);
        $lesson = BibleLesson::create($data);

        return response()->json(['lesson' => $lesson], 201);
    }

    public function show($id)
    {
        $lesson = BibleLesson::findOrFail($id);
        return response()->json(['lesson' => $lesson]);
    }

    public function update(Request $request, $id)
    {
        $lesson = BibleLesson::findOrFail($id);

        $data = $request->validate([
            'course_id' => 'sometimes|exists:bible_courses,id',
            'theme_id' => 'nullable|exists:bible_themes,id',
            'title' => 'sometimes|string|max:255',
            'order' => 'nullable|integer',
            'call_question' => 'nullable|string',
            'call_answer' => 'nullable|string',
            'scripture_verses' => 'nullable|string',
            'content' => 'nullable|string',
            'practice_task' => 'nullable|string',
            'video_url' => 'nullable|url',
            'pdf_conspect_url' => 'nullable|url',
            'is_published' => 'sometimes|boolean',
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $lesson->update($data);
        return response()->json(['lesson' => $lesson]);
    }

    public function destroy($id)
    {
        $lesson = BibleLesson::findOrFail($id);
        $lesson->delete();
        return response()->json(['message' => 'Урок удалён']);
    }

    public function togglePublish($id)
    {
        $lesson = BibleLesson::findOrFail($id);
        $lesson->is_published = !$lesson->is_published;
        $lesson->save();
        return response()->json(['lesson' => $lesson]);
    }
}