<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleCourse;
use App\Models\BibleCourseReset;
use App\Models\BibleUserLessonProgress;
use App\Models\BibleCertificate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BibleProgressController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->isStudent()) {
            return response()->json(['success' => false, 'message' => 'Только ученик'], 403);
        }

        $courses = BibleCourse::where('is_published', true)->orderBy('order')->get();
        $progressList = [];
        $totalLessons = 0;
        $completedLessons = 0;

        foreach ($courses as $course) {
            $p = $course->getProgressForUser($user->id);
            $progressList[] = [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'course_slug' => $course->slug,
                'completed' => $p['completed'],
                'total' => $p['total'],
                'percentage' => $p['percentage'],
            ];
            $totalLessons += $p['total'];
            $completedLessons += $p['completed'];
            
            // ✅ Авто-сертификаты и год выпуска
            if ($p['percentage'] >= 100) {
                $exists = BibleCertificate::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->exists();
                if (!$exists) {
                    app(BibleCertificateController::class)->generate($user->id, $course->id);
                    
                    // ✅ Уведомление студенту
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->sendCertificateIssuedNotification($user, $course);
                }
                
                // ✅ Записываем год выпуска и завершённый курс
                if (!$user->graduation_year) {
                    $user->update([
                        'graduation_year' => now()->year,
                        'graduated_course_id' => $course->id,
                    ]);
                }
            }
        }

        $overall = $totalLessons ? round(($completedLessons / $totalLessons) * 100) : 0;

        // авто-роли
        if ($overall >= 25 && !$user->hasRole('minister')) {
            $user->assignRole('minister');
        }
        if ($overall >= 50 && !$user->hasRole('group_leader')) {
            $user->assignRole('group_leader');
        }

        $level = match (true) {
            $overall >= 75 => ['name' => 'Наставник', 'icon' => 'crown'],
            $overall >= 50 => ['name' => 'Лидер', 'icon' => 'star'],
            $overall >= 25 => ['name' => 'Служитель', 'icon' => 'user-group'],
            default => ['name' => 'Ученик', 'icon' => 'academic-cap'],
        };

        return response()->json([
            'success' => true,
            'overall' => [
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'percentage' => $overall,
                'level' => $level['name'],
                'level_icon' => $level['icon'],
            ],
            'courses' => $progressList,
        ]);
    }

    public function resetCourse($courseSlug)
    {
        $user = Auth::user();
        $course = BibleCourse::where('slug', $courseSlug)->firstOrFail();
        $progress = $course->getProgressForUser($user->id);

        if ($progress['percentage'] >= 70) {
            return response()->json(['success' => false, 'message' => 'Курс уже успешно пройден'], 422);
        }

        DB::beginTransaction();
        try {
            $lessons = $course->lessons()->pluck('id');
            foreach ($lessons as $lid) {
                $old = BibleUserLessonProgress::where('user_id', $user->id)
                    ->where('lesson_id', $lid)
                    ->first();
                if ($old) {
                    BibleCourseReset::create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'lesson_id' => $lid,
                        'reset_reason' => 'retake',
                        'old_status' => $old->status,
                        'new_status' => 'not_started'
                    ]);
                    $old->delete();
                }
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Курс сброшен']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Ошибка сброса'], 500);
        }
    }
}