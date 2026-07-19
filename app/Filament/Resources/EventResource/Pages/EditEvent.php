<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function ($record) {
                    // ✅ Удаляем файл из S3
                    if ($record->thumbnail) {
                        Storage::disk('s3')->delete($record->thumbnail);
                    }
                    $record->delete();
                    
                    // Редирект после удаления
                    $this->redirect(EventResource::getUrl('index'));
                }),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
