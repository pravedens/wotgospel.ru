<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleCourse;
use App\Models\BibleLesson;
use App\Models\BibleEnrollmentRequest;
use App\Models\BibleEssay;
use App\Models\BibleUserLessonProgress;
use App\Models\User;
use Illuminate\Http\Request;

class BibleTeacherController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учителей'
            ], 403);
        }
        
        $coursesCount = BibleCourse::where('is_published', true)->count();
        $lessonsCount = BibleLesson::where('is_published', true)->count();
        $studentsCount = User::role('student')->count();
        
        $enrollmentRequests = BibleEnrollmentRequest::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'city' => $request->city,
                    'church_name' => $request->church_name,
                    'ministry' => $request->ministry,
                    'marital_status' => $request->marital_status,
                    'about' => $request->about,
                    'user' => [
                        'id' => $request->user?->id,
                        'name' => $request->user?->name,
                        'full_name' => $request->user?->full_name,
                        'email' => $request->user?->email,
                    ]
                ];
            });
            
        // Отклонённые заявки
$rejectedRequests = BibleEnrollmentRequest::with('user')
    ->where('status', 'rejected')
    ->orderBy('created_at', 'desc')
    ->get()
    ->map(function ($request) {
        return [
            'id' => $request->id,
            'user_id' => $request->user_id,
            'city' => $request->city,
            'church_name' => $request->church_name,
            'ministry' => $request->ministry,
            'marital_status' => $request->marital_status,
            'about' => $request->about,
            'user' => [
                'id' => $request->user?->id,
                'name' => $request->user?->name,
                'full_name' => $request->user?->full_name,
                'email' => $request->user?->email,
            ]
        ];
    });
        
        $pendingEssays = BibleEssay::with(['user', 'lesson', 'question'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($essay) {
                return [
                    'id' => $essay->id,
                    'content' => $essay->content,
                    'user' => [
                        'id' => $essay->user?->id,
                        'name' => $essay->user?->name,
                        'full_name' => $essay->user?->full_name,
                    ],
                    'lesson' => [
                        'id' => $essay->lesson?->id,
                        'title' => $essay->lesson?->title,
                    ],
                    'question' => [
                        'id' => $essay->question?->id,
                        'question' => $essay->question?->question,
                    ]
                ];
            });
        
        $students = User::role('student')
            ->select(['id', 'name', 'last_name', 'email', 'city', 'church_name', 'avatar'])
            ->with('assignedCourse')
            ->get()
            ->map(function ($student) {
                $totalLessons = BibleLesson::where('is_published', true)->count();
                $completedLessons = $student->bibleProgress()
                    ->whereIn('status', ['completed', 'test_passed'])
                    ->count();
                $percentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
                
                $role = $student->hasRole('group_leader') ? 'group_leader' : 'student';
                
                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'email' => $student->email,
                    'city' => $student->city,
                    'church_name' => $student->church_name,
                    'avatar_url' => $student->avatar_url,
                    'role' => $role,
                    'assigned_course_id' => $student->assigned_course_id,
                    'assigned_course' => $student->assignedCourse?->title,
                    'enrolled_year' => $student->enrolled_year,
                    'graduation_year' => $student->graduation_year,
                    'progress' => [
                        'percentage' => $percentage,
                        'completed' => $completedLessons,
                        'total' => $totalLessons
                    ]
                ];
            });
        
        return response()->json([
            'success' => true,
            'courses_count' => $coursesCount,
            'lessons_count' => $lessonsCount,
            'students_count' => $studentsCount,
            'enrollment_requests' => $enrollmentRequests,
            'rejected_requests' => $rejectedRequests,
            'pending_essays' => $pendingEssays,
            'students' => $students,
        ]);
    }
    
    public function approveRequest(Request $request, $id)
    {
        $request->validate([
            'course_id' => 'required|exists:bible_courses,id'
        ]);
    
        $enrollmentRequest = BibleEnrollmentRequest::findOrFail($id);
        $enrollmentRequest->approve(auth()->id(), $request->course_id);
    
        return response()->json(['success' => true]);
    }
    
    public function rejectRequest($id)
    {
        $request = BibleEnrollmentRequest::findOrFail($id);
        $request->reject(auth()->id());
        
        return response()->json(['success' => true]);
    }
    
    public function updateStudentRole(Request $request, $userId)
    {
        $user = $request->user();
        
        if (!$user->hasRole('teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учителей'
            ], 403);
        }
        
        $student = User::findOrFail($userId);
        
        $request->validate([
            'role' => 'required|in:student,group_leader'
        ]);
        
        $student->removeRole('student');
        $student->removeRole('group_leader');
        $student->assignRole($request->role);
        
        return response()->json(['success' => true, 'message' => 'Роль обновлена']);
    }
    
    public function reviewEssay(Request $request, $essayId)
{
    $user = $request->user();
    
    if (!$user->hasRole('teacher')) {
        return response()->json(['success' => false, 'message' => 'Доступ только для учителей'], 403);
    }
    
    $essay = BibleEssay::findOrFail($essayId);
    
    $request->validate([
        'status' => 'required|in:approved,rejected',
        'score' => 'required|integer|min:0|max:100',
        'feedback' => 'nullable|string'
    ]);
    
    if ($request->status === 'approved') {
        $essay->approve($request->score, $request->feedback, $user->id);
        
        // ✅ Обновляем прогресс урока
        $progress = BibleUserLessonProgress::where('user_id', $essay->user_id)
            ->where('lesson_id', $essay->lesson_id)
            ->first();
        
        if ($progress && $progress->status === 'test_passed') {
            $progress->markCompleted();
        }
    } else {
        $essay->reject($request->feedback, $user->id);
    }
    
    return response()->json(['success' => true, 'message' => 'Эссе проверено']);
}
    
    public function sendMessageToStudent(Request $request, $studentId)
    {
        $teacher = $request->user();
        
        if (!$teacher->hasRole('teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учителей'
            ], 403);
        }
        
        $student = User::findOrFail($studentId);
        
        $request->validate([
            'message' => 'required|string|max:5000'
        ]);
        
        $message = \App\Models\TeacherMessage::create([
            'teacher_id' => $teacher->id,
            'user_id' => $student->id,
            'sender_name' => $teacher->full_name,
            'sender_email' => $teacher->email,
            'message' => $request->message,
            'is_read' => false,
        ]);
        
        return response()->json(['success' => true, 'message' => 'Сообщение отправлено ученику']);
    }
    
    public function getStudents()
    {
        $students = User::role('student')
            ->select(['id', 'name', 'last_name', 'email', 'avatar', 'city', 'church_name', 'enrolled_year', 'graduation_year', 'assigned_course_id'])
            ->with('assignedCourse')
            ->get()
            ->map(function ($student) {
                $totalLessons = BibleLesson::where('is_published', true)->count();
                $completedLessons = $student->bibleProgress()
                    ->whereIn('status', ['completed', 'test_passed'])
                    ->count();
                $percentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
                
                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'email' => $student->email,
                    'avatar_url' => $student->avatar_url,
                    'city' => $student->city,
                    'church_name' => $student->church_name,
                    'assigned_course_id' => $student->assigned_course_id,
                    'assigned_course' => $student->assignedCourse?->title,
                    'enrolled_year' => $student->enrolled_year,
                    'graduation_year' => $student->graduation_year,
                    'role' => $student->hasRole('group_leader') ? 'group_leader' : 'student',
                    'progress' => [
                        'percentage' => $percentage,
                        'completed' => $completedLessons,
                        'total' => $totalLessons
                    ]
                ];
            });
        
        return response()->json(['success' => true, 'students' => $students]);
    }
    
    /**
     * Назначить курс студенту
     */
    public function updateStudentCourse(Request $request, $userId)
    {
        $user = $request->user();
        
        if (!$user->hasRole('teacher')) {
            return response()->json(['success' => false, 'message' => 'Доступ только для учителей'], 403);
        }
        
        $student = User::findOrFail($userId);
        
        $request->validate([
            'course_id' => 'nullable|exists:bible_courses,id'
        ]);
        
        $student->assigned_course_id = $request->course_id;
        $student->save();
        
        return response()->json(['success' => true, 'message' => 'Курс назначен']);
    }
    
    /**
     * Снять роль студента
     */
    public function removeStudentRole($userId)
{
    $user = Auth::user();
    
    if (!$user->hasRole('teacher')) {
        return response()->json(['success' => false, 'message' => 'Доступ только для учителей'], 403);
    }
    
    $student = User::findOrFail($userId);
    $student->removeRole('student');
    $student->removeRole('group_leader');
    
    // ✅ Удаляем или меняем статус заявки
    BibleEnrollmentRequest::where('user_id', $userId)
        ->where('status', 'approved')
        ->update(['status' => 'pending']);
    
    return response()->json(['success' => true, 'message' => 'Роль студента снята']);
}
}