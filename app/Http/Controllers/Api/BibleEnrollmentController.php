<?php
// app/Http/Controllers/Api/BibleEnrollmentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleEnrollmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BibleEnrollmentController extends Controller
{
    
    /**
     * Подать заявку на обучение
     */
    public function store(Request $request)
{
    \Log::info('Enrollment request course_id', ['course_id' => $request->course_id]);
    try {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Необходимо авторизоваться'
            ], 401);
        }

        if ($user->isEnrolledInSchool()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы уже зачислены в школу'
            ], 422);
        }

        $existingRequest = BibleEnrollmentRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Ваша заявка уже рассматривается'
            ], 422);
        }

        // ✅ Добавить validation для course_id
        $request->validate([
            'agreement_accepted' => 'required|accepted',
            'course_id' => 'required|exists:bible_courses,id', // Добавить
        ]);

        $enrollmentRequest = BibleEnrollmentRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'course_id' => $request->course_id, // ✅ Сохраняем курс
            'city' => $request->city ?? null,
            'church_name' => $request->church_name ?? null,
            'phone' => $request->phone ?? null,
            'birth_date' => $request->birth_date ?? null,
            'about' => $request->about ?? null,
            'marital_status' => $request->marital_status ?? null,
            'gender' => $request->gender ?? null,
            'ministry' => $request->ministry ?? null,
            'bible_courses_experience' => $request->bible_courses_experience ?? null,
            'learning_expectations' => $request->learning_expectations ?? null,
            'agreement_accepted' => true,
            'agreement_accepted_at' => now(),
            'agreement_ip' => $request->ip(),
        ]);

        // Обновляем профиль
        $updateData = [];
        if ($request->filled('city')) $updateData['city'] = $request->city;
        if ($request->filled('church_name')) $updateData['church_name'] = $request->church_name;
        if ($request->filled('phone')) $updateData['phone'] = $request->phone;
        if ($request->filled('birth_date')) $updateData['birth_date'] = $request->birth_date;
        if ($request->filled('about')) $updateData['about'] = $request->about;
        if ($request->filled('marital_status')) $updateData['marital_status'] = $request->marital_status;
        if ($request->filled('gender')) $updateData['gender'] = $request->gender;
        if ($request->filled('ministry')) $updateData['ministry'] = $request->ministry;
        if ($request->filled('bible_courses_experience')) $updateData['bible_courses_experience'] = $request->bible_courses_experience;
        if ($request->filled('learning_expectations')) $updateData['learning_expectations'] = $request->learning_expectations;
        
        if (!empty($updateData)) {
            $user->update($updateData);
        }

        // ✅ Передаём course_id в метод уведомлений
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyTeachersAboutEnrollment($user, $request, $request->course_id);

        return response()->json([
            'success' => true,
            'message' => 'Заявка на обучение отправлена'
        ]);

    } catch (\Exception $e) {
        \Log::error('Enrollment error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Ошибка: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Получить статус заявки текущего пользователя
     */
    public function status()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Необходимо авторизоваться'
            ], 401);
        }

        if ($user->isEnrolledInSchool()) {
            return response()->json([
                'success' => true,
                'is_enrolled' => true,
                'role' => $user->getHighestRole()
            ]);
        }

        $request = BibleEnrollmentRequest::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$request) {
            return response()->json([
                'success' => true,
                'is_enrolled' => false,
                'has_request' => false
            ]);
        }

        return response()->json([
            'success' => true,
            'is_enrolled' => false,
            'has_request' => true,
            'status' => $request->status,
            'requested_at' => $request->created_at
        ]);
    }
    
    public function unblock($userId)
{
    $user = Auth::user();
    
    if (!$user->hasRole('teacher')) {
        return response()->json(['success' => false, 'message' => 'Доступ только для учителей'], 403);
    }
    
    // Удаляем отклонённую заявку пользователя
    BibleEnrollmentRequest::where('user_id', $userId)
        ->where('status', 'rejected')
        ->delete();
    
    return response()->json(['success' => true]);
}
}