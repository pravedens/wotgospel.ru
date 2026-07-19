<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Chat\MessageSent;
use App\Chat\ConversationCreated;
use App\Chat\SendMessageNotifications;
use Illuminate\Support\Facades\DB;
use App\Services\CensorService;

class ChatService
{
    protected CensorService $censorService;

    public function __construct(CensorService $censorService)
    {
        $this->censorService = $censorService;
    }

    /**
     * Получить все беседы пользователя
     */
    public function getUserConversations(int $userId): array
    {
        $conversations = Conversation::forUser($userId)
            ->with(['user1', 'user2', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get();

        return $conversations->map(function ($conversation) use ($userId) {
            $otherUser = $conversation->getOtherUser($userId);
            $lastMessage = $conversation->lastMessage;

            return [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'other_user' => $otherUser ? [
                    'id' => $otherUser->id,
                    'full_name' => $otherUser->full_name,
                    'avatar_url' => $otherUser->avatar_url,
                ] : null,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'message' => $lastMessage->message,
                    'created_at' => $lastMessage->created_at,
                    'sender_id' => $lastMessage->sender_id,
                ] : null,
                'unread_count' => $conversation->getUnreadCount($userId),
                'last_message_at' => $conversation->last_message_at,
            ];
        })->toArray();
    }

    /**
     * Получить сообщения беседы
     */
    public function getMessages(int $conversationId, int $userId, int $limit = 50): array
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->hasUser($userId)) {
            throw new \Exception('Вы не участник этой беседы');
        }

        $messages = $conversation->messages()
            ->with(['sender:id,name,last_name,avatar'])
            ->approved()
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $conversation->markAsRead($userId);

        return $messages->map(function ($message) use ($userId) {
            return [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->full_name ?? 'Пользователь',
                'sender_avatar' => $message->sender->avatar_url ?? null,
                'receiver_id' => $message->receiver_id,
                'message' => $message->message,
                'type' => $message->type,
                'attachments' => $message->attachments,
                'is_mine' => (int) $message->sender_id === (int) $userId,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Отправить сообщение
     */
    public function sendMessage(
        int $senderId,
        int $receiverId,
        string $message,
        array $attachments = [],
        string $type = 'text'
    ): array {
        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        // ============================================
        // ПРОВЕРКА ПРАВ ДЛЯ ОТПРАВКИ СООБЩЕНИЯ
        // ============================================
        if ($senderId === $receiverId) {
            throw new \Exception('Нельзя отправить сообщение самому себе');
        }

        // Определяем роли
        $senderRole = $sender->getHighestRole();
        $receiverRole = $receiver->getHighestRole();

        // Роли с правом отправлять сообщения
        $allowedRoles = ['super_admin', 'pastor', 'admin', 'teacher', 'group_leader', 'student'];

        if (!in_array($senderRole, $allowedRoles)) {
            throw new \Exception('У вас нет прав для отправки сообщений');
        }

        // Запрещаем только user → user (обычные пользователи)
        if ($senderRole === 'user' && $receiverRole === 'user') {
            throw new \Exception('Обычные пользователи не могут общаться друг с другом');
        }

        // Если у пользователя нет роли teacher, student, group_leader — запрещаем
        $senderSchoolRoles = ['teacher', 'student', 'group_leader', 'pastor', 'admin', 'super_admin'];
        if (!in_array($senderRole, $senderSchoolRoles)) {
            throw new \Exception('У вас нет прав для отправки сообщений');
        }

        // Цензура
        $isCensored = $this->censorService->containsProfanity($message);
        $originalMessage = $isCensored ? $message : null;
        $messageText = $isCensored ? $this->censorService->censor($message) : $message;

        DB::beginTransaction();

        try {
            $conversation = Conversation::findOrCreate($senderId, $receiverId);

            if ($conversation->wasRecentlyCreated) {
                broadcast(new ConversationCreated($conversation));
            }

            $messageModel = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $messageText,
                'type' => $type,
                'attachments' => $attachments,
                'is_read' => false,
                'is_delivered' => false,
                'is_approved' => true,
                'is_censored' => $isCensored,
                'original_message' => $originalMessage,
            ]);

            $conversation->update([
                'last_message_at' => now(),
            ]);

            DB::commit();

            broadcast(new MessageSent($messageModel, $conversation->id));
            SendMessageNotifications::dispatch($messageModel);

            return [
                'id' => $messageModel->id,
                'conversation_id' => $conversation->id,
                'sender_id' => $messageModel->sender_id,
                'receiver_id' => $messageModel->receiver_id,
                'message' => $messageModel->message,
                'type' => $messageModel->type,
                'attachments' => $messageModel->attachments,
                'is_mine' => true,
                'is_read' => false,
                'created_at' => $messageModel->created_at->toISOString(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Получить количество непрочитанных сообщений
     */
    public function getUnreadCount(int $userId): int
    {
        return Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();
    }
}