<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TeacherCourseController extends Controller
{
    public function index()
{
    $courses = BibleCourse::orderBy('order')
        ->select('id', 'title', 'slug', 'description', 'image_url', 'what_you_will_learn', 'skills', 'price', 'certificate_text', 'statuses', 'order', 'is_published')
        ->withCount('lessons')
        ->get();
    
    return response()->json(['courses' => $courses]);
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_published' => 'sometimes|boolean',
            'what_you_will_learn' => 'nullable|string',
            'skills' => 'nullable|string',
            'price' => 'nullable|string',
            'certificate_text' => 'nullable|string',
            'statuses' => 'nullable|array',
        ]);

        $data['slug'] = Str::slug($data['title']);
        $data['statuses'] = $data['statuses'] ?? [
            ['name' => 'Ученик', 'percentage' => 0, 'icon' => '📘'],
            ['name' => 'Служитель', 'percentage' => 25, 'icon' => '🙏'],
            ['name' => 'Лидер', 'percentage' => 50, 'icon' => '👑'],
            ['name' => 'Наставник', 'percentage' => 75, 'icon' => '⭐'],
        ];

        $course = BibleCourse::create($data);
        return response()->json(['course' => $course], 201);
    }

    public function show($id)
    {
        $course = BibleCourse::with(['themes', 'lessons'])->findOrFail($id);
        return response()->json(['course' => $course]);
    }

    public function update(Request $request, $id)
    {
        $course = BibleCourse::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_published' => 'sometimes|boolean',
            'what_you_will_learn' => 'nullable|string',
            'skills' => 'nullable|string',
            'price' => 'nullable|string',
            'certificate_text' => 'nullable|string',
            'statuses' => 'nullable|array',
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $course->update($data);
        return response()->json(['course' => $course]);
    }

    public function destroy($id)
    {
        $course = BibleCourse::findOrFail($id);
        $course->delete();
        return response()->json(['message' => 'Курс удалён']);
    }

    public function togglePublish($id)
    {
        $course = BibleCourse::findOrFail($id);
        $course->is_published = !$course->is_published;
        $course->save();
        return response()->json(['course' => $course]);
    }
}