<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Новое сообщение</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #2b6cb0;">✉️ Новое сообщение</h2>
        <p style="font-size: 16px; color: #333;">
            <strong>{{ $sender_name }}</strong> отправил(а) вам сообщение:
        </p>
        <div style="background: #f0f4f8; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p style="font-size: 15px; color: #2d3748; white-space: pre-wrap;">{{ $message }}</p>
        </div>
        <a href="{{ $siteUrl }}" style="display: inline-block; background: #2b6cb0; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-top: 10px;">
            Перейти в личный кабинет
        </a>
        <p style="font-size: 12px; color: #718096; margin-top: 20px;">
            Это автоматическое уведомление. Не отвечайте на это письмо.
        </p>
    </div>
</body>
</html>