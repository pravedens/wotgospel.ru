<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HasMainSiteButton;
use BackedEnum; // Подключаем трейт с кнопкой
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use HasMainSiteButton; // Добавляем кнопку на главную сайта

    // Можно также настроить заголовок страницы
    protected static ?string $title = 'Панель управления';

    // Можно настроить иконку в меню (опционально)
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    // Настройка сортировки в меню (опционально)
    protected static ?int $navigationSort = 1;
}
