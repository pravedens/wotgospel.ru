<?php

namespace App\Filament\Resources\BibleTestQuestionResource\Pages;

use App\Filament\Resources\BibleTestQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBibleTestQuestion extends CreateRecord
{
    protected static string $resource = BibleTestQuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Нормализуем данные
        $data = BibleTestQuestionResource::normalizeFormDataBeforeSave($data);

        return $data;
    }
    
        protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}