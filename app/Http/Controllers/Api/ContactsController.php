<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ContactsController extends Controller
{
    /**
     * Send contact message
     */
    public function send(Request $request)
    {
        try {
            // Проверка авторизации
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Только авторизованные пользователи могут отправлять сообщения'
                ], 401);
            }
            
            $user = auth()->user();
            
            // Защита от дублей на уровне сессии
            $sessionKey = 'contact_sent_' . session()->getId();
            if (Cache::get($sessionKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сообщение уже отправлено. Пожалуйста, подождите.'
                ], 429);
            }
            
            // Валидация
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:5000'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Очистка от тегов
            $message = strip_tags($request->message);
            
            // Данные для сохранения
            $data = [
                'name' => $user->name,
                'email' => $user->email,
                'message' => $message,
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'is_read' => false,
            ];
            
            // Логируем
            Log::info('Contact form submitted', [
                'name' => $data['name'],
                'email' => $data['email'],
                'user_id' => $data['user_id'],
                'message_length' => strlen($data['message'])
            ]);
            
            // Сохраняем в базу данных
            $contact = ContactMessage::create($data);
            Log::info('Contact message saved', ['contact_id' => $contact->id]);
            
            // Отправляем email уведомления
            $this->sendEmailNotifications($data, $contact->id);
            
            // Сохраняем флаг отправки в кэш на 5 минут
            Cache::put($sessionKey, true, 300);
            
            return response()->json([
                'success' => true,
                'message' => 'Сообщение отправлено'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Contact form error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера. Попробуйте позже.'
            ], 500);
        }
    }
    
    /**
     * Send email notifications to admins
     */
    private function sendEmailNotifications(array $data, int $contactId): void
    {
        // Получаем email для отправки из .env
        $adminEmails = env('CONTACT_ADMIN_EMAILS', 'admin@wotgospel.ru');
        $emails = array_map('trim', explode(',', $adminEmails));
        $emails = array_unique($emails);
        
        // Добавляем основной email если его нет в списке
        $mainEmail = env('MAIL_FROM_ADDRESS', 'admin@wotgospel.ru');
        if (!in_array($mainEmail, $emails)) {
            $emails[] = $mainEmail;
        }
        
        Log::info('Sending contact email to: ' . implode(', ', $emails));
        
        // Отправляем каждому получателю
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid email address skipped: ' . $email);
                continue;
            }
            
            try {
                $this->sendSingleEmail($email, $data, $contactId);
                Log::info('Email sent successfully to: ' . $email);
            } catch (\Exception $e) {
                Log::error('Failed to send email to ' . $email . ': ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Send single email
     */
    private function sendSingleEmail(string $email, array $data, int $contactId): void
    {
        Mail::send([], [], function ($mail) use ($email, $data, $contactId) {
            $mail->to($email)
                 ->subject('Новое сообщение от ' . $data['name'])
                 ->replyTo($data['email'], $data['name'])
                 ->html($this->getEmailHtml($data, $contactId));
        });
    }
    
    /**
     * Get email HTML content
     */
    private function getEmailHtml(array $data, int $contactId): string
    {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Новое сообщение</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4f46e5; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px; }
                    .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
                    .info { background: #e5e7eb; padding: 15px; border-radius: 8px; margin: 15px 0; }
                    .info p { margin: 5px 0; }
                    .message-box { background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #4f46e5; margin-top: 15px; }
                    .badge { display: inline-block; background: #4f46e5; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-bottom: 15px; }
                    .button { display: inline-block; background: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin-top: 15px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>📬 Новое сообщение</h2>
                    </div>
                    <div class="content">
                        <div class="badge">Сообщение #' . $contactId . '</div>
                        
                        <div class="info">
                            <p><strong>👤 От:</strong> ' . htmlspecialchars($data['name']) . '</p>
                            <p><strong>📧 Email:</strong> <a href="mailto:' . htmlspecialchars($data['email']) . '">' . htmlspecialchars($data['email']) . '</a></p>
                            <p><strong>🆔 Пользователь:</strong> ID ' . $data['user_id'] . '</p>
                            <p><strong>📅 Дата:</strong> ' . now()->format('d.m.Y H:i:s') . '</p>
                            <p><strong>🌐 IP:</strong> ' . htmlspecialchars($data['ip']) . '</p>
                        </div>
                        
                        <div class="message-box">
                            <h3 style="margin-top: 0;">💬 Сообщение:</h3>
                            <p style="white-space: pre-wrap;">' . nl2br(htmlspecialchars($data['message'])) . '</p>
                        </div>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="https://wotnt.ru/admin/contact-messages/' . $contactId . '" class="button">📖 Посмотреть в админке</a>
                        </div>
                    </div>
                    <div class="footer">
                        <p>Сообщение отправлено через форму обратной связи wotnt.ru</p>
                        <p style="font-size: 11px;">Это автоматическое сообщение, пожалуйста, не отвечайте на него.</p>
                    </div>
                </div>
            </body>
            </html>
        ';
    }
    
    /**
     * Get all messages (for admin)
     */
    public function index(Request $request)
    {
        $messages = ContactMessage::with('user')
            ->when($request->unread, function ($query) {
                return $query->unread();
            })
            ->when($request->email, function ($query, $email) {
                return $query->fromEmail($email);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);
        
        return response()->json($messages);
    }
    
    /**
     * Get single message (for admin)
     */
    public function show($id)
    {
        $message = ContactMessage::with('user')->findOrFail($id);
        
        return response()->json($message);
    }
    
    /**
     * Mark message as read (for admin)
     */
    public function markAsRead($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение отмечено как прочитанное'
        ]);
    }
    
    /**
     * Delete message (for admin)
     */
    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение удалено'
        ]);
    }
}