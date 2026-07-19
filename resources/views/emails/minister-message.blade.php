<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Новое сообщение</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 20px; text-align: center; border-radius: 12px 12px 0 0; }
        .content { background: #f9fafb; padding: 20px; border-radius: 0 0 12px 12px; }
        .message-box { background: white; padding: 15px; border-left: 4px solid #4f46e5; margin: 15px 0; border-radius: 8px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #6b7280; }
        .reply-link { display: inline-block; background: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin-top: 15px; }
        .info-row { margin-bottom: 10px; }
        .info-label { font-weight: bold; color: #4f46e5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✉️ Новое сообщение от прихожанина</h2>
        </div>
        
        <div class="content">
            <div class="info-row">
                <span class="info-label">Отправитель:</span> {{ $sender_name }}
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span> {{ $sender_email }}
            </div>
            <div class="info-row">
                <span class="info-label">Дата:</span> {{ now()->format('d.m.Y H:i') }}
            </div>
            
            <div class="message-box">
                <strong>📝 Сообщение:</strong>
                <p style="margin-top: 10px;">{{ nl2br(e($messageText)) }}</p>
            </div>
            
            <div style="text-align: center;">
                <a href="mailto:{{ $sender_email }}" class="reply-link">📧 Ответить отправителю</a>
            </div>
        </div>
        
        <div class="footer">
            <p>Это сообщение отправлено через сайт {{ config('app.name') }}.</p>
            <p>Для управления уведомлениями зайдите в <a href="{{ config('app.frontend_url') }}/dashboard">личный кабинет</a>.</p>
        </div>
    </div>
</body>
</html>