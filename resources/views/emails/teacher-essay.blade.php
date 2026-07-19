<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Новое эссе</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1e40af, #6b21a5); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e5e7eb; }
        .message-box { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 20px 0; }
        .button { display: inline-block; background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✍️ Новое эссе</h2>
        </div>
        <div class="content">
            <p><strong>Ученик:</strong> {{ $student_name }}</p>
            <p><strong>Email:</strong> <a href="mailto:{{ $student_email }}">{{ $student_email }}</a></p>
            <p><strong>Урок:</strong> {{ $lesson_title }}</p>
            <div class="message-box">
                <p><strong>Эссе (начало):</strong></p>
                <p>{{ nl2br(e($essay_preview)) }}</p>
            </div>
            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">📋 Перейти к проверке</a>
            </div>
        </div>
        <div class="footer">
            <p>© {{ $year }} {{ $churchName }}. Все права защищены.</p>
        </div>
    </div>
</body>
</html>