<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Message;
use App\Services\ChatService;
use App\Chat\TypingStarted;
use App\Chat\TypingStopped;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function getConversations(Request $request)
    {
        try {
            $user = $request->user();
            $conversations = $this->chatService->getUserConversations($user->id);

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getMessages(Request $request, int $conversationId)
    {
        try {
            $user = $request->user();
            $limit = $request->input('limit', 50);

            $messages = $this->chatService->getMessages($conversationId, $user->id, $limit);

            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required|exists:users,id',
                'message' => 'required|string|max:10000',
                'attachments' => 'nullable|array',
                'type' => 'nullable|string|in:text,image,file',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $message = $this->chatService->sendMessage(
                $user->id,
                $request->receiver_id,
                $request->message,
                $request->attachments ?? [],
                $request->type ?? 'text'
            );

            return response()->json([
                'success' => true,
                'message' => 'Сообщение отправлено',
                'data' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUnreadCount(Request $request)
    {
        try {
            $user = $request->user();
            $count = $this->chatService->getUnreadCount($user->id);

            return response()->json([
                'success' => true,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'count' => 0,
            ], 500);
        }
    }

    public function findOrCreate(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $conversation = \App\Models\Conversation::findOrCreate($user->id, $request->user_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead(Request $request, int $conversationId)
    {
        try {
            $user = $request->user();
            $conversation = \App\Models\Conversation::findOrFail($conversationId);

            if (!$conversation->hasUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы не участник этой беседы',
                ], 403);
            }

            $conversation->markAsRead($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Беседа отмечена как прочитанная',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function typingStarted(Request $request, int $conversationId)
    {
        try {
            $user = $request->user();

            broadcast(new TypingStarted(
                $conversationId,
                $user->id,
                $user->full_name
            ))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Статус печатания обновлён',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function typingStopped(Request $request, int $conversationId)
    {
        try {
            $user = $request->user();

            broadcast(new TypingStopped(
                $conversationId,
                $user->id
            ))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Статус печатания обновлён',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTeachers(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isStudent()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ только для учеников'
                ], 403);
            }
            
            $teachers = User::role('teacher')->get(['id', 'name', 'last_name', 'email', 'avatar']);
            
            $chats = $teachers->map(function ($teacher) use ($user) {
                $lastMessage = Message::where(function($q) use ($user, $teacher) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $teacher->id);
                })->orWhere(function($q) use ($user, $teacher) {
                    $q->where('sender_id', $teacher->id)->where('receiver_id', $user->id);
                })->latest()->first();
                
                $unreadCount = Message::where('sender_id', $teacher->id)
                    ->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->count();
                
                return [
                    'teacher_id' => $teacher->id,
                    'full_name' => $teacher->full_name,
                    'avatar_url' => $teacher->avatar_url,
                    'last_message' => $lastMessage ? $lastMessage->message : null,
                    'unread_count' => $unreadCount,
                ];
            });
            
            return response()->json([
                'success' => true,
                'chats' => $chats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Поиск пользователей для чата
     */
    public function searchUsers(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $currentUserId = $request->user()->id;

            if (strlen($query) < 2) {
                return response()->json(['users' => []]);
            }

            $users = User::where('id', '!=', $currentUserId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('last_name', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->with('roles')
                ->limit(20)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'avatar_url' => $user->avatar_url,
                        'roles' => $user->roles->pluck('name')->toArray(),
                    ];
                });

            return response()->json([
                'success' => true,
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}