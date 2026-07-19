{{-- resources/views/emails/events/new.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новое событие: {{ $event->title }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fb;
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
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header .badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        .content {
            padding: 30px;
        }
        .event-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
            padding-left: 15px;
        }
        .info-block {
            background-color: #f0f9ff;
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
        .description {
            background-color: #f9fafb;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
        }
        .description h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #374151;
        }
        .description p {
            margin: 0;
            color: #4b5563;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        .unsubscribe {
            margin-top: 15px;
            font-size: 11px;
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
            .info-label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div style="padding: 20px;">
        <div class="container">
            <div class="header">
                <h1>🆕 Новое событие</h1>
                <div class="badge">Церковь "Слово Истины"</div>
            </div>
            
            <div class="content">
                <div class="event-title">
                    {{ $event->title }}
                </div>
                
                <div class="info-block">
                    <div class="info-row">
                        <div class="info-icon">📅</div>
                        <div class="info-label">Дата:</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($event->startDate)->translatedFormat('d F Y') }}</div>
                    </div>
                    
                    @if($event->startTime)
                    <div class="info-row">
                        <div class="info-icon">⏰</div>
                        <div class="info-label">Время:</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($event->startTime)->format('H:i') }}</div>
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
                
                @if($event->description)
                <div class="description">
                    <h3>📝 О событии:</h3>
                    <p>{{ $event->description }}</p>
                </div>
                @endif
                
                <div style="text-align: center;">
                    <a href="https://wotnt.ru/events/{{ $event->slug }}" class="button">
                        Подробнее о событии →
                    </a>
                </div>
            </div>
            
            <div class="footer">
                <p>© {{ date('Y') }} Церковь "Слово Истины"</p>
                <p>г. Нижний Тагил, ул. Чехова, д. 10</p>
                <div class="unsubscribe">
                    Вы получили это письмо, потому что подписаны на уведомления о новых событиях.<br>
                    Отписаться можно в <a href="https://wotnt.ru/dashboard">личном кабинете</a>.
                </div>
            </div>
        </div>
    </div>
</body>
</html>