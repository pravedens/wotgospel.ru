<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Новое сообщение</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1e40af, #6b21a5); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e5e7eb; }
        .message-box { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 20px 0; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 20px; }
        .button { display: inline-block; background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✉️ Новое сообщение</h2>
        </div>
        <div class="content">
            <p><strong>Отправитель:</strong> {{ $sender_name }}</p>
            <p><strong>Email:</strong> {{ $sender_email }}</p>
            <p><strong>Дата:</strong> {{ now()->format('d.m.Y H:i') }}</p>
            
            <div class="message-box">
                <p><strong>Сообщение:</strong></p>
                <p>{{ nl2br(e($messageText)) }}</p>
            </div>
            
            <a href="{{ $dashboardUrl }}?tab=teacher" class="button">Перейти в панель учителя</a>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} {{ $churchName }}. Все права защищены.</p>
        </div>
    </div>
</body>
</html>