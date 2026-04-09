<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h2 class="text-2xl font-bold mb-2">
                Здравствуйте, {{ auth()->user()->name }}!
            </h2>
            <p class="text-gray-600 dark:text-gray-300">
                Добро пожаловать в ваш личный кабинет
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('filament.user.pages.profile') }}" 
               class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-2">👤</div>
                <h3 class="font-semibold">Мой профиль</h3>
                <p class="text-sm text-gray-500">Редактировать личные данные</p>
            </a>
        </div>
    </div>
</x-filament-panels::page>