<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ваше эссе проверено</title>
</head>
<body>
    <h2>{{ $is_approved ? '✅ Эссе одобрено!' : '❌ Эссе требует доработки' }}</h2>
    <p>Здравствуйте, {{ $student_name }}!</p>
    <p>Ваше эссе к уроку <strong>«{{ $lesson_title }}»</strong> проверено.</p>
    @if($is_approved)
        <p><strong>Оценка:</strong> {{ $score }}/100</p>
    @endif
    <p><strong>Отзыв учителя:</strong></p>
    <p>{{ nl2br(e($feedback)) }}</p>
    <p><a href="{{ $dashboardUrl }}">Перейти к уроку</a></p>
    <hr>
    <p>© {{ $year }} {{ $churchName }}</p>
</body>
</html>