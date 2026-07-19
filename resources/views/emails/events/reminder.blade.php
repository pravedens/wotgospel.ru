{{-- resources/views/emails/events/reminder.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Напоминание: {{ $event->title }} сегодня!</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fef3c7;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header .emoji {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .alert-box {
            background-color: #fffbeb;
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        .alert-box h2 {
            margin: 0 0 10px 0;
            color: #d97706;
            font-size: 20px;
        }
        .alert-box p {
            margin: 0;
            color: #92400e;
        }
        .event-title {
            font-size: 24px;
            font-weight: 700;
            color: #d97706;
            margin-bottom: 20px;
            text-align: center;
        }
        .info-block {
            background-color: #fef9e8;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-icon {
            font-size: 20px;
            min-width: 30px;
        }
        .info-label {
            font-weight: 600;
            color: #374151;
            min-width: 80px;
        }
        .info-value {
            color: #1f2937;
        }
        .time-warning {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px;
            margin: 20px 0;
            border-radius: 8px;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        @media (max-width: 600px) {
            .content {
                padding: 20px;
            }
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div style="padding: 20px;">
        <div class="container">
            <div class="header">
                <div class="emoji">🔔</div>
                <h1>Событие сегодня!</h1>
            </div>
            
            <div class="content">
                <div class="alert-box">
                    <h2>Не пропустите!</h2>
                    <p>{{ $event->title }} состоится сегодня</p>
                </div>
                
                <div class="event-title">
                    {{ $event->title }}
                </div>
                
                <div class="info-block">
                    @if($event->startTime)
                    <div class="info-row">
                        <div class="info-icon">⏰</div>
                        <div class="info-label">Время начала:</div>
                        <div class="info-value"><strong>{{ \Carbon\Carbon::parse($event->startTime)->format('H:i') }}</strong></div>
                    </div>
                    @endif
                    
                    @if($event->location)
                    <div class="info-row">
                        <div class="info-icon">📍</div>
                        <div class="info-label">Место:</div>
                        <div class="info-value">{{ $event->location }}</div>
                    </div>
                    @endif
                </div>
                
                @if($event->startTime)
                <div class="time-warning">
                    ⚠️ Пожалуйста, приходите за 10-15 минут до начала мероприятия.
                </div>
                @endif
                
                <div style="text-align: center;">
                    <a href="https://wotnt.ru/events/{{ $event->slug }}" class="button">
                        Подробная информация →
                    </a>
                </div>
            </div>
            
            <div class="footer">
                <p>© {{ date('Y') }} Церковь "Слово Истины"</p>
                <p>Ждём вас!</p>
            </div>
        </div>
    </div>
</body>
</html>