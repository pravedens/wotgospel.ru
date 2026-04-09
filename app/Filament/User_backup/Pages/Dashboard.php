<?php

namespace App\Filament\User\Pages;
use BackedEnum;

use Filament\Pages\Page;
use Filament\Actions\Action;

class Dashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';
    
    protected string $view = 'filament.user.pages.dashboard';
    
    protected static ?string $navigationLabel = 'Личный кабинет';
    
    protected static ?string $title = 'Личный кабинет';
    
    protected static ?string $slug = 'dashboard';
    
    protected static ?int $navigationSort = 1;
    
        
    protected function getHeaderActions(): array
    {
        return [
            Action::make('goToDashboard')
                ->label('На главную сайта')
                ->url('/')
                ->icon('heroicon-o-home')
                ->color('gray'),
        ];
    }
}