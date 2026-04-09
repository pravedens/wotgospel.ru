<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Post;

class DocumentProxyController extends Controller
{
    public function show(Request $request, $path)
    {
        // Декодируем путь
        $path = urldecode($path);
        $filePath = 'posts/text/' . $path;
        
        Log::info('📄 DocumentProxy request', [
            'path' => $path,
            'full_path' => $filePath
        ]);
        
        // Проверяем существование файла
        if (!Storage::disk('s3')->exists($filePath)) {
            Log::error('❌ File not found', ['path' => $filePath]);
            return response()->json(['error' => 'File not found'], 404);
        }
        
        // Получаем файл
        $file = Storage::disk('s3')->get($filePath);
        
        // Определяем MIME-тип
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match($extension) {
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
        
        // Ищем пост по имени файла
        $post = Post::where('text_file', 'LIKE', '%' . $path)->first();
        
        // Определяем имя для отображения
        $displayName = 'document.' . $extension; // Запасной вариант
        
        if ($post) {
            // Используем имя из базы данных
            if ($post->text_filename) {
                $displayName = $post->text_filename;
            } else {
                // Генерируем из заголовка проповеди
                $displayName = $post->title . '.' . $extension;
            }
            Log::info('✅ Using post name', ['display_name' => $displayName]);
        } else {
            // Пробуем получить имя из параметра запроса
            $queryName = $request->query('name');
            if ($queryName) {
                $displayName = urldecode($queryName);
                Log::info('✅ Using query name', ['display_name' => $displayName]);
            }
        }
        
        // Очищаем имя от нежелательных символов
        $displayName = preg_replace('/[^\w\s\.-]/u', '', $displayName);
        
        // Формируем заголовки для Office Online
        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $displayName . '"',
            'Content-Length' => strlen($file),
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ];
        
        // Добавляем заголовок для Office Online (важно!)
        $headers['X-WOPI-ItemVersion'] = '1.0';
        $headers['X-WOPI-ItemName'] = $displayName;
        
        return response($file, 200, $headers);
    }
}