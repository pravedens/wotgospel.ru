<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ContactsController extends Controller
{
    /**
     * Get list of available recipients by roles
     */
    public function getRecipients()
    {
        try {
            $recipients = [];
            
            // Получаем пользователей по ролям и собираем их email'ы
            $roles = ['pastor', 'minister', 'pray', 'super_admin'];
            
            foreach ($roles as $role) {
                $users = User::role($role)->get();
                $emails = $users->pluck('email')->filter()->values()->toArray();
                
                // Названия ролей для отображения
                $roleNames = [
                    'pastor' => 'Пастору',
                    'minister' => 'Служителям',
                    'pray' => 'Молитвенникам',
                    'super_admin' => 'Администратору сайта'
                ];
                
                $recipients[] = [
                    'role' => $role,
                    'name' => $roleNames[$role],
                    'emails' => $emails,
                    'count' => count($emails),
                    'description' => $this->getRoleDescription($role),
                    'has_recipients' => count($emails) > 0  // 👈 ДОБАВЛЕНО
                ];
            }
            
            return response()->json([
                'success' => true,
                'recipients' => $recipients
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting recipients: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения списка получателей'
            ], 500);
        }
    }
    
    /**
     * Get role description
     */
    private function getRoleDescription(string $role): string
    {
        return match($role) {
            'pastor' => 'Сообщение будет отправлено пастору церкви',
            'minister' => 'Сообщение получат все служители церкви',
            'pray' => 'Ваше сообщение увидят молитвенники, которые будут молиться за вас',
            'super_admin' => 'Сообщение будет отправлено администратору сайта',
            default => ''
        };
    }
    
    /**
 * Get public list of recipients (for guests)
 */
public function getPublicRecipients()
{
    try {
        $recipients = [];
        
        $roles = ['pastor', 'minister', 'pray', 'super_admin'];
        
        foreach ($roles as $role) {
            $users = User::role($role)->get();
            $emails = $users->pluck('email')->filter()->values()->toArray();
            
            $roleNames = [
                'pastor' => 'Пастору',
                'minister' => 'Служителям',
                'pray' => 'Молитвенникам',
                'super_admin' => 'Администратору сайта'
            ];
            
            $recipients[] = [
                'role' => $role,
                'name' => $roleNames[$role],
                'count' => count($emails),
                'description' => $this->getRoleDescription($role),
                'has_recipients' => count($emails) > 0
            ];
        }
        
        return response()->json([
            'success' => true,
            'recipients' => $recipients
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error getting public recipients: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'recipients' => []
        ], 500);
    }
}
    
    /**
     * Send contact message
     */
    public function send(Request $request)
{
    try {
        $user = auth()->user();
        $isAuthenticated = $user !== null;
        
        // Валидация
        $rules = [
            'message' => 'required|string|max:5000',
            'recipient_role' => 'required|string|in:pastor,minister,pray,super_admin'
        ];
        
        if (!$isAuthenticated) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|max:255';
            $rules['captcha_token'] = 'required|string';
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Проверка капчи для неавторизованных
        if (!$isAuthenticated) {
            $captchaValid = $this->verifyCaptcha($request->captcha_token);
            if (!$captchaValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Проверка капчи не пройдена'
                ], 422);
            }
        }
        
        // Защита от дублей
        $sessionKey = 'contact_sent_' . session()->getId();
        if (Cache::get($sessionKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Сообщение уже отправлено. Пожалуйста, подождите.'
            ], 429);
        }
        
        // Очистка от тегов
        $message = strip_tags($request->message);
        $recipientRole = $request->recipient_role;
        
        // Данные отправителя
        if ($isAuthenticated) {
            $name = $user->name;
            $email = $user->email;
            $userId = $user->id;
        } else {
            $name = strip_tags($request->name);
            $email = strip_tags($request->email);
            $userId = null;
        }
        
        // Получаем email'ы получателей
        $recipientEmails = $this->getRecipientEmailsByRole($recipientRole);
        
        if (empty($recipientEmails)) {
            return response()->json([
                'success' => false,
                'message' => 'Не удалось определить получателя сообщения.'
            ], 400);
        }
        
        // Данные для сохранения
        $data = [
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'user_id' => $userId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_read' => false,
            'recipient_role' => $recipientRole,
            'recipient_emails_list' => implode(', ', $recipientEmails)
        ];
        
        // Сохраняем в БД
        $contact = ContactMessage::create($data);
        
        // Отправляем email уведомления
        $this->sendEmailNotifications($data, $recipientEmails, $contact->id);
        
        // Сохраняем флаг отправки в кэш на 5 минут
        Cache::put($sessionKey, true, 300);
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение отправлено'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Contact form error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Ошибка сервера. Попробуйте позже.'
        ], 500);
    }
}

/**
 * Verify Yandex Captcha
 */
private function verifyCaptcha($token): bool
{
    $secretKey = config('services.yandex.captcha.secret_key');
    
    if (!$secretKey) {
        Log::warning('Yandex Captcha secret key not configured');
        return false;
    }
    
    try {
        $response = Http::asForm()->post('https://smartcaptcha.cloud.yandex.ru/validate', [
            'secret' => $secretKey,
            'token' => $token,
            'ip' => request()->ip(),
        ]);
        
        $data = $response->json();
        
        return isset($data['status']) && $data['status'] === 'ok';
    } catch (\Exception $e) {
        Log::error('Captcha verification failed: ' . $e->getMessage());
        return false;
    }
}
    
    /**
     * Get recipient emails by role (только из базы данных)
     */
    private function getRecipientEmailsByRole(string $role): array
    {
        // Получаем пользователей с этой ролью
        $users = User::role($role)->get();
        $emails = $users->pluck('email')->filter()->values()->toArray();
        
        return $emails;
    }
    
    /**
     * Send email notifications to selected recipients
     */
    private function sendEmailNotifications(array $data, array $recipientEmails, int $contactId): void
    {
        // Убираем дубли
        $emails = array_unique($recipientEmails);
        
        Log::info('Sending contact email to recipients: ' . implode(', ', $emails));
        
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
        $roleNames = [
            'pastor' => 'Пастору',
            'minister' => 'Служителям',
            'pray' => 'Молитвенникам',
            'super_admin' => 'Администратору'
        ];
        
        $roleName = $roleNames[$data['recipient_role']] ?? $data['recipient_role'];
        
        Mail::send([], [], function ($mail) use ($email, $data, $contactId, $roleName) {
            $mail->to($email)
                 ->subject('Новое сообщение от ' . $data['name'] . ' (' . $roleName . ')')
                 ->replyTo($data['email'], $data['name'])
                 ->html($this->getEmailHtml($data, $contactId, $roleName));
        });
    }
    
    /**
     * Get email HTML content
     */
    private function getEmailHtml(array $data, int $contactId, string $roleName): string
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
                    .recipient-badge { background: #10b981; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>📬 Новое сообщение</h2>
                    </div>
                    <div class="content">
                        <div class="badge">Сообщение #' . $contactId . '</div>
                        <div class="badge recipient-badge">Получатель: ' . htmlspecialchars($roleName) . '</div>
                        
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
            ->when($request->recipient_role, function ($query, $role) {
                return $query->where('recipient_role', $role);
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