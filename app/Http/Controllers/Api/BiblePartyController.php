<?php
// app/Http/Controllers/Api/BiblePartyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleParty;
use App\Models\BiblePartyMessage;
use App\Services\CensorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiblePartyController extends Controller
{
    protected $censorService;
    
    public function __construct(CensorService $censorService)
    {
        $this->censorService = $censorService;
    }
    
    /**
     * Информация о текущей группе пользователя
     */
    public function myParty()
    {
        $user = Auth::user();
        $party = $user->bibleActiveParty();
        
        if (!$party) {
            return response()->json(['success' => true, 'has_party' => false]);
        }
        
        $isLeader = $party->leader_id === $user->id;
        
        $partyData = [
            'id' => $party->id,
            'name' => $party->name,
            'description' => $party->description,
            'course_id' => $party->course_id,
            'course_title' => $party->course->title,
            'join_code' => $party->join_code,
            'meeting_day' => $party->meeting_day,
            'meeting_time' => $party->meeting_time,
            'zoom_link' => $party->zoom_link,
            'max_students' => $party->max_students,
            'current_students' => $party->activeStudents()->count(),
            'is_leader' => $isLeader,
            'leader_id' => $party->leader_id,
            'is_active' => $party->is_active
        ];
        
        if ($isLeader) {
            $students = $party->activeStudents()
                ->with('bibleProgress')
                ->get(['users.id', 'users.name', 'users.last_name', 'users.email']);
            
            foreach ($students as $student) {
                $student->full_name = $student->full_name;
                $student->course_progress = $student->getCourseProgress($party->course_id);
            }
            
            $partyData['students'] = $students;
        }
        
        return response()->json([
            'success' => true,
            'has_party' => true,
            'party' => $partyData
        ]);
    }
    
    /**
     * Получить сообщения чата
     */
    public function getMessages()
    {
        $user = Auth::user();
        $party = $user->bibleActiveParty();
        
        if (!$party) {
            return response()->json([
                'success' => false,
                'message' => 'Вы не состоите в группе'
            ], 404);
        }
        
        $messages = BiblePartyMessage::where('party_id', $party->id)
            ->with('user:id,name,last_name,avatar')
            ->orderBy('created_at', 'asc')
            ->get(['id', 'user_id', 'message', 'created_at', 'is_censored']);
        
        // Если сообщение было подвергнуто цензуре, показываем звёздочки
        foreach ($messages as $message) {
            if ($message->is_censored) {
                $message->message = $this->censorService->censor($message->message);
            }
        }
        
        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    /**
     * Отправить сообщение с цензурой
     */
    public function sendMessage(Request $request)
    {
        $user = Auth::user();
        $party = $user->bibleActiveParty();
        
        if (!$party) {
            return response()->json([
                'success' => false,
                'message' => 'Вы не состоите в группе'
            ], 404);
        }
        
        // Чат закрыт, если нет лидера
        if (!$party->leader_id) {
            return response()->json([
                'success' => false,
                'message' => 'Чат недоступен: не назначен лидер группы'
            ], 403);
        }
        
        // Чат закрыт, если меньше 2 участников (включая лидера)
        if ($party->activeStudents()->count() < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Чат недоступен: недостаточно участников'
            ], 403);
        }
        
        $request->validate([
            'message' => 'required|string|max:5000'
        ]);
        
        $originalMessage = $request->message;
        $isCensored = $this->censorService->containsProfanity($originalMessage);
        
        if ($isCensored) {
            // Не пропускаем сообщение с матом
            return response()->json([
                'success' => false,
                'message' => 'Сообщение содержит нецензурные слова или оскорбления. Оно не будет отправлено.'
            ], 422);
        }
        
        $message = BiblePartyMessage::create([
            'party_id' => $party->id,
            'user_id' => $user->id,
            'message' => $originalMessage,
            'is_approved' => true,
            'is_censored' => false
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение отправлено',
            'message_id' => $message->id
        ]);
    }
    
    /**
     * Удалить сообщение (только для лидера или учителя)
     */
    public function deleteMessage($messageId)
    {
        $user = Auth::user();
        $message = BiblePartyMessage::findOrFail($messageId);
        $party = $message->party;
        
        if ($party->leader_id !== $user->id && !$user->hasRole('teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Только лидер группы или учитель могут удалять сообщения'
            ], 403);
        }
        
        $message->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение удалено'
        ]);
    }
    
    /**
     * Удалить участника из группы (только для лидера)
     */
    public function removeStudent($userId)
    {
        $user = Auth::user();
        $party = $user->bibleActiveParty();
        
        if (!$party || $party->leader_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Только лидер группы может удалять участников'
            ], 403);
        }
        
        if ($userId == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Лидер не может удалить сам себя'
            ], 422);
        }
        
        $party->removeStudent($userId);
        
        return response()->json([
            'success' => true,
            'message' => 'Участник удалён из группы'
        ]);
    }
    
    /**
     * Вступить в группу по коду
     */
    public function join(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Только ученики могут вступать в группы'
            ], 403);
        }
        
        $request->validate([
            'join_code' => 'required|string|size:6'
        ]);
        
        $party = BibleParty::where('join_code', strtoupper($request->join_code))
            ->where('is_active', true)
            ->first();
        
        if (!$party) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный код приглашения'
            ], 404);
        }
        
        if ($user->bibleActiveParty()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы уже состоите в группе'
            ], 422);
        }
        
        if ($party->activeStudents()->count() >= $party->max_students) {
            return response()->json([
                'success' => false,
                'message' => 'Группа достигла максимального количества участников'
            ], 422);
        }
        
        $party->addStudent($user->id);
        
        return response()->json([
            'success' => true,
            'message' => "Вы вступили в группу \"{$party->name}\"",
            'party' => $party
        ]);
    }
    
    /**
     * Выйти из группы
     */
    public function leave()
    {
        $user = Auth::user();
        $party = $user->bibleActiveParty();
        
        if (!$party) {
            return response()->json([
                'success' => false,
                'message' => 'Вы не состоите в группе'
            ], 404);
        }
        
        $party->removeStudent($user->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Вы вышли из группы'
        ]);
    }
}