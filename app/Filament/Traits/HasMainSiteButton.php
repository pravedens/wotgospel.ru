<?php

namespace App\Filament\Traits;

use Filament\Actions\Action;

trait HasMainSiteButton
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('goToMainSite')
                ->label('На главную сайта')
                ->url('/')
                ->icon('heroicon-o-globe-alt')
                ->color('gray'),
        ];
    }
}