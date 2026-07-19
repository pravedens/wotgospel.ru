<?php
// app/Filament/Resources/BibleCourseResource/Pages/CreateBibleCourse.php

namespace App\Filament\Resources\BibleCourseResource\Pages;

use App\Filament\Resources\BibleCourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBibleCourse extends CreateRecord
{
    protected static string $resource = BibleCourseResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}