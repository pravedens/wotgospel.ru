<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $lesson->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #4a5568;
        }
        .header h1 {
            color: #2d3748;
            font-size: 24px;
            margin: 0;
        }
        .header .course {
            color: #718096;
            font-size: 14px;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c5282;
            border-left: 4px solid #4299e1;
            padding-left: 12px;
            margin-bottom: 15px;
        }
        .call-question {
            background-color: #ebf8ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .call-answer {
            background-color: #dbeafe;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .scripture-verses {
            background-color: #fefcbf;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content img {
            max-width: 100%;
            height: auto;
        }
        .practice-task {
            background-color: #e6fffa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $lesson->title }}</h1>
        <div class="course">{{ $lesson->course->title }}</div>
    </div>

    @if($lesson->call_question)
    <div class="section">
        <div class="section-title">1. Призыв</div>
        <div class="call-question">
            {!! nl2br(e($lesson->call_question)) !!}
        </div>
        @if($lesson->call_answer)
        <div class="call-answer">
            <strong>📖 Библейский ответ:</strong><br>
            {!! nl2br(e($lesson->call_answer)) !!}
        </div>
        @endif
    </div>
    @endif

    @if($lesson->scripture_verses)
    <div class="section">
        <div class="section-title">2. Писание</div>
        <div class="scripture-verses">
            {!! nl2br(e($lesson->scripture_verses)) !!}
        </div>
    </div>
    @endif

    @if($lesson->content)
    <div class="section">
        <div class="section-title">3. Содержание урока</div>
        <div class="content">
            {!! $content ?? $lesson->content !!}
        </div>
    </div>
    @endif

    @if($lesson->practice_task)
    <div class="section">
        <div class="section-title">4. Практическое задание</div>
        <div class="practice-task">
            {!! nl2br(e($lesson->practice_task)) !!}
        </div>
    </div>
    @endif

    <div class="footer">
        <p>© {{ date('Y') }} Местная религиозная организация Церковь Христиан Веры Евангельской (пятидесятников) "СЛОВО ИСТИНЫ" г.Нижнего Тагила. | Все права защищены</p>
    </div>
</body>
</html>