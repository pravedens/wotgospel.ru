<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ Слово Истины</title>
    
    <link rel="icon" type="image/png" href="/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="MyWebSite" />
    <link rel="manifest" href="/favicon/site.webmanifest" />
    
    <!-- Filament стили для красивого отображения -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @filamentStyles
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .container {
            text-align: center;
            max-width: 800px;
            padding: 2rem;
        }
        
        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 350px;
            font-size: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .notification.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .notification.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .notification.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .notification.info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .notification strong {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            transition: all 0.3s ease;
            min-width: 160px;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            border-color: white;
        }
        
        .nav-links .admin-link {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }
        
        .nav-links .admin-link:hover {
            background: rgba(245, 158, 11, 0.3);
            border-color: #fbbf24;
        }
        
        .nav-links .user-link {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }
        
        .nav-links .user-link:hover {
            background: rgba(16, 185, 129, 0.3);
            border-color: #34d399;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 10px;
            background: rgba(255,255,255,0.2);
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
            text-align: left;
        }
        
        .feature {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .feature h3 {
            margin-top: 0;
            font-size: 1.3rem;
        }
    </style>
</head>
<body>
    <!-- Контейнер для уведомлений -->
    <div id="notifications-container"></div>

    <div class="container">
        <img src="{{ asset('images/logo.png') }}" alt="Логотип" style="max-width: 200px;">
        <h1>Админ Слово Истины</h1>
        <p class="subtitle">Добро пожаловать на наш сайт! Мы рады видеть вас здесь.</p>
        
        @auth
    @php
        $user = auth()->user();
        $currentUrl = url()->current();
        
        // Проверяем, есть ли у пользователя доступ к админ-панели (любая роль кроме 'user')
        $canAccessAdmin = false;
        foreach($user->roles as $role) {
            if($role->name !== 'user') {
                $canAccessAdmin = true;
                break;
            }
        }
    @endphp
    
    <!-- Пользователь авторизован -->
    <div class="user-info">
        <span>👋 Привет, <strong>{{ $user->name }}</strong></span>
        @if($canAccessAdmin)
            <span class="role-badge">Администратор</span>
        @else
            <span class="role-badge">Пользователь</span>
        @endif
    </div>
    
    <div class="nav-links">
        <!-- Ссылка на админ-панель для всех, у кого есть доступ (кроме тех, кто уже в админке) -->
        @if($canAccessAdmin && !str_contains($currentUrl, '/admin'))
            <a href="{{ url('/admin') }}" class="admin-link">
                👨‍💼 Admin-панель
            </a>
        @endif
        
        <!-- Ссылка в личный кабинет ТОЛЬКО для обычных пользователей (у кого нет доступа к админке) -->
        @if(!$canAccessAdmin && !str_contains($currentUrl, '/account'))
            <a href="{{ url('/account') }}" class="user-link">
                👤 User-панель
            </a>
        @endif
        
    </div>
@else
    <!-- Пользователь не авторизован -->
    <div class="nav-links">
        <a href="{{ url('/admin/login') }}" class="admin-link">
            👨‍💼 Вход для администраторов
        </a>
        <a href="{{ url('/account/login') }}" class="user-link">
            👤 Вход для пользователей
        </a>
    </div>
@endauth

        <div class="features">
            <div class="feature">
                <h3>📚 Библиотека ресурсов</h3>
                <p>Доступ к обширной коллекции материалов и документов</p>
            </div>
            <div class="feature">
                <h3>📅 Календарь событий</h3>
                <p>Будьте в курсе всех предстоящих мероприятий и встреч</p>
            </div>
            <div class="feature">
                <h3>👥 Сообщество</h3>
                <p>Общайтесь с единомышленниками и делитесь опытом</p>
            </div>
        </div>
    </div>

    <!-- Скрипты Filament -->
    @filamentScripts
    @livewireScripts
    
    <script>
    const notificationsContainer = document.getElementById('notifications-container');

    function showNotification(detail) {
        const notification = document.createElement('div');
        notification.className = `notification ${detail.status}`;
        notification.innerHTML = `<strong>${detail.title}</strong>${detail.body}`;
        notificationsContainer.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        const errorCookie = getCookie('access_error');
        if (errorCookie) {
            showNotification({
                status: 'danger',
                title: '⛔ Доступ запрещён',
                body: errorCookie
            });
            document.cookie = 'access_error=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        }
        
        @if(session()->has('registration_success'))
            showNotification({
                status: 'success',
                title: 'Регистрация успешна! ',
                body: 'Письмо с подтверждением отправлено на {{ session('registration_email') }}. Пожалуйста, проверьте почту.'
            });
        @endif

        @if(session()->has('password_reset_sent'))
            showNotification({
                status: 'success',
                title: 'Письмо отправлено',
                body: 'Если указанный email зарегистрирован, вы получите письмо с инструкциями.'
            });
        @endif

        @if(session()->has('filament.notifications'))
            @foreach(session()->get('filament.notifications') as $notification)
                showNotification({
                    status: '{{ $notification['status'] }}',
                    title: '{{ $notification['title'] }} ',
                    body: '{{ $notification['body'] }}'
                });
            @endforeach
        @endif
    });

    window.addEventListener('show-notification', (e) => {
        showNotification(e.detail);
    });
</script>
</body>
</html>