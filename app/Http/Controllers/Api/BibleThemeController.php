<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleTheme;
use App\Models\BibleCourse;
use Illuminate\Http\Request;

class BibleThemeController extends Controller
{
    /**
     * Получить все темы (без фильтра по курсу)
     */
    public function indexAll()
    {
        $themes = BibleTheme::with('course')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'description', 'order', 'course_id']);
        
        return response()->json(['success' => true, 'themes' => $themes]);
    }
    
    /**
     * Получить темы по slug курса
     */
    public function index($courseSlug)
    {
        $course = BibleCourse::where('slug', $courseSlug)->firstOrFail();
        $themes = $course->publishedThemes()
            ->withCount('lessons')
            ->get(['id', 'title', 'slug', 'description', 'order']);
        
        return response()->json(['success' => true, 'themes' => $themes]);
    }
    
    /**
     * Получить уроки темы
     */
    public function lessons($themeSlug)
    {
        $theme = BibleTheme::where('slug', $themeSlug)
            ->where('is_published', true)
            ->firstOrFail();
        
        $lessons = $theme->publishedLessons()
            ->orderBy('order')
            ->get(['id', 'title', 'slug', 'order']);
        
        return response()->json(['success' => true, 'theme' => $theme, 'lessons' => $lessons]);
    }
}