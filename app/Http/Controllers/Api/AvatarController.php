<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    /**
     * Загрузка аватара пользователя
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Удаляем старый аватар, если есть
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Сохраняем новый аватар
        $path = $request->file('avatar')->store('avatars', 'public');

        // Обновляем пользователя
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'message' => 'Аватар успешно загружен',
            'avatar' => $path,
            'avatar_url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Удаление аватара
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return response()->json([
            'message' => 'Аватар удален'
        ]);
    }

    /**
     * Получение текущего аватара
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null
        ]);
    }
}