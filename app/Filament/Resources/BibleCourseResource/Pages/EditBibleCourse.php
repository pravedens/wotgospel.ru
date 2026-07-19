<?php
// app/Filament/Resources/BibleCourseResource/Pages/EditBibleCourse.php

namespace App\Filament\Resources\BibleCourseResource\Pages;

use App\Filament\Resources\BibleCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBibleCourse extends EditRecord
{
    protected static string $resource = BibleCourseResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}