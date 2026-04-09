<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Загрузка файла
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'type' => 'required|in:thumbnail,audio,text',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');
        
        // Определяем директорию
        $directory = match($type) {
            'thumbnail' => 'posts/thumbnails',
            'audio' => 'posts/audio',
            'text' => 'posts/text',
            default => 'posts',
        };
        
        // Генерируем уникальное имя
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) 
                  . '-' . uniqid() 
                  . '.' . $extension;
        
        try {
            // Сохраняем файл на S3
            $path = $file->storeAs($directory, $filename, 's3');
            
            // Получаем URL из S3
            $url = Storage::disk('s3')->url($path);
            
            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => $url,
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удаление файла
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');
        
        try {
            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
                return response()->json([
                    'success' => true,
                    'message' => 'Файл удален'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Загрузка чанками (для очень больших файлов)
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'type' => 'required|in:thumbnail,audio,text',
            'chunk' => 'required|integer',
            'chunks' => 'required|integer',
            'uuid' => 'required|string',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');
        $chunk = $request->input('chunk');
        $chunks = $request->input('chunks');
        $uuid = $request->input('uuid');
        
        $directory = match($type) {
            'thumbnail' => 'posts/thumbnails',
            'audio' => 'posts/audio',
            'text' => 'posts/text',
            default => 'posts',
        };
        
        // Временная директория для чанков
        $tmpDir = 'tmp/' . $uuid;
        
        // Сохраняем чанк
        $chunkPath = $tmpDir . '/' . str_pad($chunk, 4, '0', STR_PAD_LEFT);
        Storage::disk('s3')->put($chunkPath, file_get_contents($file));
        
        // Если это последний чанк - собираем файл
        if ($chunk == $chunks - 1) {
            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) 
                      . '-' . uniqid() 
                      . '.' . $extension;
            
            $finalPath = $directory . '/' . $filename;
            
            // Собираем все чанки
            $finalContent = '';
            for ($i = 0; $i < $chunks; $i++) {
                $chunkContent = Storage::disk('s3')->get($tmpDir . '/' . str_pad($i, 4, '0', STR_PAD_LEFT));
                $finalContent .= $chunkContent;
            }
            
            // Сохраняем итоговый файл
            Storage::disk('s3')->put($finalPath, $finalContent);
            
            // Удаляем временные чанки
            Storage::disk('s3')->deleteDirectory($tmpDir);
            
            $url = Storage::disk('s3')->url($finalPath);
            
            return response()->json([
                'success' => true,
                'path' => $finalPath,
                'url' => $url,
                'filename' => $file->getClientOriginalName(),
                'size' => strlen($finalContent),
                'mime' => $file->getMimeType(),
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Чанк загружен',
            'chunk' => $chunk,
            'total' => $chunks
        ]);
    }
}