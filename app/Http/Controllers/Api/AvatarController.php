<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarController extends Controller
{
    /**
     * Загрузка аватара пользователя на S3
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Удаляем старый аватар, если есть
        if ($user->avatar) {
            Storage::disk('s3')->delete($user->avatar);
        }

        // Генерируем уникальное имя файла
        $file = $request->file('avatar');
        $filename = 'avatars/' . Str::random(40) . '.' . $file->getClientOriginalExtension();

        // Сохраняем на S3
        $path = Storage::disk('s3')->put($filename, file_get_contents($file), 'public');

        // Обновляем пользователя
        $user->avatar = $filename;
        $user->save();

        // Формируем URL для доступа к аватару
        $avatarUrl = Storage::disk('s3')->url($filename);

        return response()->json([
            'success' => true,
            'message' => 'Аватар успешно загружен',
            'avatar' => $filename,
            'avatar_url' => $avatarUrl
        ]);
    }

    /**
     * Удаление аватара с S3
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('s3')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Аватар удален'
        ]);
    }

    /**
     * Получение текущего аватара
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $avatarUrl = null;
        if ($user->avatar) {
            $avatarUrl = Storage::disk('s3')->url($user->avatar);
        }

        return response()->json([
            'success' => true,
            'avatar' => $user->avatar,
            'avatar_url' => $avatarUrl
        ]);
    }
}