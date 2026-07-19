{{-- resources/views/emails/events/day-before.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Напоминание: {{ $event->title }} завтра!</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e0f2fe;
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
            background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
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
        .countdown {
            background-color: #f0f9ff;
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #e0f2fe;
        }
        .countdown-text {
            font-size: 18px;
            color: #0284c7;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .event-title {
            font-size: 24px;
            font-weight: 700;
            color: #0284c7;
            margin-bottom: 20px;
            text-align: center;
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
        .prepare-list {
            background-color: #f9fafb;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
        }
        .prepare-list h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #374151;
        }
        .prepare-list ul {
            margin: 0;
            padding-left: 20px;
            color: #4b5563;
        }
        .prepare-list li {
            margin: 5px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
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
        .calendar-link {
            margin-top: 15px;
            text-align: center;
        }
        .calendar-link a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
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
                <div class="emoji">📅</div>
                <h1>Напоминание о событии</h1>
            </div>
            
            <div class="countdown">
                <div class="countdown-text">
                    ⏰ Событие состоится <strong>ЗАВТРА</strong>!
                </div>
            </div>
            
            <div class="content">
                <div class="event-title">
                    {{ $event->title }}
                </div>
                
                <div class="info-block">
                    <div class="info-row">
                        <div class="info-icon">📅</div>
                        <div class="info-label">Дата:</div>
                        <div class="info-value">
                            <strong>{{ \Carbon\Carbon::parse($event->startDate)->translatedFormat('d F Y') }}</strong>
                            ({{ \Carbon\Carbon::parse($event->startDate)->translatedFormat('l') }})
                        </div>
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
                
                <div class="prepare-list">
                    <h3>📋 Что подготовить:</h3>
                    <ul>
                        <li>✅ Записную книжку или блокнот для заметок</li>
                        <li>✅ Библию (если есть возможность)</li>
                        <li>✅ Хорошее настроение и открытое сердце</li>
                        <li>✅ Пригласите с собой друзей!</li>
                    </ul>
                </div>
                
                <div style="text-align: center;">
                    <a href="https://wotnt.ru/events/{{ $event->slug }}" class="button">
                        Подробнее о событии →
                    </a>
                </div>
                
                <div class="calendar-link">
                    <a href="#">
                        📌 Добавить в календарь (Google/Apple)
                    </a>
                </div>
            </div>
            
            <div class="footer">
                <p>© {{ date('Y') }} Церковь "Слово Истины"</p>
                <p>Ждём вас завтра!</p>
                <div class="unsubscribe" style="margin-top: 10px; font-size: 11px;">
                    Вы получили это письмо, потому что подписаны на напоминания о событиях.<br>
                    Отписаться можно в <a href="https://wotnt.ru/dashboard">личном кабинете</a>.
                </div>
            </div>
        </div>
    </div>
</body>
</html>