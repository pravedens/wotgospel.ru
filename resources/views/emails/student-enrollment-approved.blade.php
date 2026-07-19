<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Вы зачислены на обучение</title>
</head>
<body>
    <h2>🎉 Поздравляем, {{ $student_name }}!</h2>
    <p>Ваша заявка на обучение одобрена.</p>
    <p>Вы зачислены на курс: <strong>{{ $course_name }}</strong></p>
    <p>Перейдите в <a href="{{ $dashboardUrl }}">личный кабинет</a>, чтобы начать обучение.</p>
    <hr>
    <p>© {{ $year }} {{ $churchName }}</p>
</body>
</html>