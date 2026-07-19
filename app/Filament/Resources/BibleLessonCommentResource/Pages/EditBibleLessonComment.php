<?php
// app/Filament/Resources/BibleLessonCommentResource/Pages/EditBibleLessonComment.php

namespace App\Filament\Resources\BibleLessonCommentResource\Pages;

use App\Filament\Resources\BibleLessonCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBibleLessonComment extends EditRecord
{
    protected static string $resource = BibleLessonCommentResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}