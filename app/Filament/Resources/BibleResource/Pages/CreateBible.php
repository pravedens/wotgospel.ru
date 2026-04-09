<?php

namespace App\Filament\Resources\BibleResource\Pages;

use App\Filament\Resources\BibleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBible extends CreateRecord
{
    protected static string $resource = BibleResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
