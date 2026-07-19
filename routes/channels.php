<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;

// ============================================
// ПОЛЬЗОВАТЕЛЬСКИЙ КАНАЛ
// ============================================
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['sanctum']]);

// ============================================
// ЕДИНЫЙ КАНАЛ ДЛЯ ЧАТА
// ============================================
Broadcast::channel('conversation.{id}', function ($user, $id) {
    Log::info('Broadcast auth attempt', [
        'auth_user_id' => $user?->id,
        'conversation_id' => $id,
        'channel_name' => request('channel_name'),
        'socket_id' => request('socket_id'),
    ]);

    $conversation = Conversation::find($id);

    if (! $conversation) {
        Log::warning('Broadcast auth failed: conversation not found', [
            'conversation_id' => $id,
            'channel_name' => request('channel_name'),
        ]);
        return false;
    }

    Log::info('Broadcast conversation found', [
        'conversation_id' => $conversation->id,
        'user1_id' => $conversation->user1_id,
        'user2_id' => $conversation->user2_id,
        'auth_user_id' => $user?->id,
        'has_user' => $conversation->hasUser((int) $user->id),
        'channel_name' => request('channel_name'),
    ]);

    if (! $conversation->hasUser((int) $user->id)) {
        Log::warning('Broadcast auth failed: user is not participant', [
            'conversation_id' => $conversation->id,
            'user1_id' => $conversation->user1_id,
            'user2_id' => $conversation->user2_id,
            'auth_user_id' => $user?->id,
            'channel_name' => request('channel_name'),
        ]);
        return false;
    }

    $result = [
        'id' => $user->id,
        'name' => $user->full_name ?? $user->name ?? 'Пользователь',
        'avatar_url' => $user->avatar_url ?? null,
    ];

    Log::info('Broadcast auth success', [
        'conversation_id' => $conversation->id,
        'auth_user_id' => $user->id,
        'channel_name' => request('channel_name'),
        'result' => $result,
    ]);

    return $result;
}, ['guards' => ['sanctum']]);

// ============================================
// КАНАЛ ДЛЯ ПОЛЬЗОВАТЕЛЯ
// ============================================
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['sanctum']]);