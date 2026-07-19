<?php

namespace App\Filament\Resources\BibleThemeResource\Pages;

use App\Filament\Resources\BibleThemeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBibleTheme extends EditRecord
{
    protected static string $resource = BibleThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}