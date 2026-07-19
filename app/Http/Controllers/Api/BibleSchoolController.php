<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleCourse;
use App\Models\BibleEnrollmentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class BibleSchoolController extends Controller
{
    public function index(Request $request)
    {
        $user = null;
        $token = $request->bearerToken();
        
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && !$accessToken->expires_at?->isPast()) {
                $user = $accessToken->tokenable;
                $user->load('roles');
            }
        }
        
        // Получаем курсы (фильтруем по назначенному курсу для студентов)
        $coursesQuery = BibleCourse::where('is_published', true)->orderBy('order');
        
        if ($user && $user->hasRole('student') && $user->assigned_course_id) {
            $coursesQuery->where('id', $user->assigned_course_id);
        }
        
        $courses = $coursesQuery->with(['themes' => function($q) {
            $q->where('is_published', true)
              ->orderBy('order')
              ->with(['lessons' => function($q2) {
                  $q2->where('is_published', true)
                     ->orderBy('order')
                     ->select('id', 'title', 'slug', 'order', 'theme_id');
              }]);
        }])->get(['id', 'title', 'slug', 'description', 'image_url']);
        
        $teachers = $this->getTeachers();
        
        $coursesWithData = $courses->map(function ($course) use ($teachers, $user) {
            $progress = $user && $user->hasRole('student') 
                ? $course->getProgressForUser($user->id) 
                : null;
            
            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'image_url' => $course->image_url,
                'progress' => $progress,
                'teachers' => $teachers,
                'themes' => $course->themes->map(function ($theme) {
                    return [
                        'id' => $theme->id,
                        'title' => $theme->title,
                        'description' => $theme->description,
                        'lessons_count' => $theme->lessons->count(),
                        'lessons' => $theme->lessons->map(function ($lesson) {
                            return [
                                'id' => $lesson->id,
                                'title' => $lesson->title,
                                'slug' => $lesson->slug,
                            ];
                        }),
                    ];
                }),
            ];
        });
        
        $hasCourses = $courses->isNotEmpty();
        
        $response = [
            'teachers' => $teachers,
            'courses' => $coursesWithData,
            'enrollment_status' => 'guest',
            'user_can_apply' => false,
            'message' => null
        ];
        
        // Учитель
        if ($user && $user->hasRole('teacher')) {
            $response['enrollment_status'] = 'teacher';
            $response['message'] = 'Вы вошли как преподаватель';
            $response['user_can_apply'] = false;
            return response()->json($response);
        }
        
        // Студент
        if ($user && $user->hasRole('student')) {
            $response['enrollment_status'] = 'approved';
            $response['courses'] = $coursesWithData;
            $response['message'] = 'Вы зачислены на обучение';
            $response['user_can_apply'] = false;
            return response()->json($response);
        }
        
        // Гость
        if (!$user) {
            $response['message'] = 'Зарегистрируйтесь, чтобы подать заявку на обучение';
            return response()->json($response);
        }
        
        // Авторизованный пользователь без роли student
        $enrollmentRequest = BibleEnrollmentRequest::where('user_id', $user->id)->latest()->first();
        
        if ($enrollmentRequest) {
            switch ($enrollmentRequest->status) {
                case 'pending':
                    $response['enrollment_status'] = 'pending';
                    $response['message'] = 'Ваша заявка на обучение рассматривается';
                    $response['user_can_apply'] = false;
                    break;
                case 'approved':
                    $response['enrollment_status'] = 'approved';
                    $response['courses'] = $coursesWithData;
                    $response['message'] = 'Вы зачислены на обучение!';
                    $response['user_can_apply'] = false;
                    break;
                case 'rejected':
                    $response['enrollment_status'] = 'rejected';
                    $response['message'] = 'Ваша заявка отклонена. Свяжитесь с администратором';
                    $response['user_can_apply'] = false;
                    break;
                default:
                    $response['enrollment_status'] = 'none';
                    $response['user_can_apply'] = $hasCourses;
                    $response['message'] = $hasCourses ? 'Заполните анкету для зачисления' : 'Курсы скоро появятся';
            }
        } else {
            $response['enrollment_status'] = 'none';
            $response['user_can_apply'] = $hasCourses;
            $response['message'] = $hasCourses ? 'Заполните анкету для зачисления' : 'Курсы скоро появятся';
        }
        
        return response()->json($response);
    }
    
    private function getTeachers()
    {
        return User::role('teacher')
            ->select(['id', 'name', 'last_name', 'avatar', 'about'])
            ->get()
            ->map(fn ($teacher) => [
                'id' => $teacher->id,
                'full_name' => $teacher->full_name,
                'avatar_url' => $teacher->avatar_url,
                'about' => $teacher->about,
            ]);
    }
    
    public function graduates(Request $request)
    {
        $year = $request->get('year');
        
        $query = User::role('student')
            ->whereNotNull('graduation_year')
            ->whereNotNull('graduated_course_id')
            ->with('assignedCourse');
        
        if ($year) {
            $query->where('graduation_year', $year);
        }
        
        $graduates = $query->orderBy('graduation_year', 'desc')
            ->get(['id', 'name', 'last_name', 'avatar', 'graduation_year', 'graduated_course_id']);
        
        $years = User::role('student')
            ->whereNotNull('graduation_year')
            ->distinct()
            ->orderBy('graduation_year', 'desc')
            ->pluck('graduation_year');
        
        return response()->json([
            'graduates' => $graduates->map(fn($g) => [
                'id' => $g->id,
                'full_name' => $g->full_name,
                'avatar_url' => $g->avatar_url,
                'graduation_year' => $g->graduation_year,
                'course_title' => $g->assignedCourse?->title,
            ]),
            'years' => $years,
        ]);
    }
}