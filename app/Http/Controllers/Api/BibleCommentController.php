<?php
// app/Http/Controllers/Api/BibleCommentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleLesson;
use App\Models\BibleLessonComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BibleCommentController extends Controller
{
    /**
     * Получить комментарии к уроку
     */
    public function index($lessonSlug)
    {
        $lesson = BibleLesson::where('slug', $lessonSlug)->firstOrFail();

        $comments = BibleLessonComment::where('lesson_id', $lesson->id)
            ->where('is_approved', true)
            ->whereNull('parent_id')
            ->with(['user', 'replies' => function($query) {
                $query->where('is_approved', true)->with('user');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments
        ]);
    }

    /**
     * Добавить комментарий к уроку
     */
    public function store(Request $request, $lessonSlug)
    {
        $user = Auth::user();
        $lesson = BibleLesson::where('slug', $lessonSlug)->firstOrFail();

        if (!$user || !$user->isEnrolledInSchool()) {
            return response()->json([
                'success' => false,
                'message' => 'Только ученики школы могут оставлять комментарии'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|min:3|max:5000',
            'parent_id' => 'nullable|exists:bible_lesson_comments,id'
        ]);

        $comment = BibleLessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
            'is_approved' => false // Требует модерации
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Комментарий отправлен на модерацию',
            'comment' => $comment
        ]);
    }
}