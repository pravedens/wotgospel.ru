<?php

namespace App\Filament\Resources\BibleTestQuestionResource\Pages;

use App\Filament\Resources\BibleTestQuestionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBibleTestQuestion extends EditRecord
{
    protected static string $resource = BibleTestQuestionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Декодируем config если строка
        if (isset($data['config']) && is_string($data['config'])) {
            $decoded = json_decode($data['config'], true);
            $data['config'] = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        // Гарантируем что config — массив
        if (! isset($data['config']) || ! is_array($data['config'])) {
            $data['config'] = [];
        }

        // Для multiple_choice — удаляем correct из данных, так как форма его не использует
        if (($data['type'] ?? null) === 'multiple_choice') {
            unset($data['config']['correct']);
        }

        // Для true_false — гарантируем поля
        if (($data['type'] ?? null) === 'true_false') {
            if (! isset($data['config']['statement'])) {
                $data['config']['statement'] = $data['question'] ?? '';
            }
            if (! isset($data['config']['correct'])) {
                $data['config']['correct'] = '1';
            }
            if (! isset($data['config']['explanation'])) {
                $data['config']['explanation'] = '';
            }
        }

        // Для verse_reference — гарантируем поля
        if (($data['type'] ?? null) === 'verse_reference') {
            if (! isset($data['config']['expected_book'])) {
                $data['config']['expected_book'] = '';
            }
            if (! isset($data['config']['expected_chapter'])) {
                $data['config']['expected_chapter'] = null;
            }
            if (! isset($data['config']['expected_verse'])) {
                $data['config']['expected_verse'] = null;
            }
            if (! isset($data['config']['accept_alternative_notations'])) {
                $data['config']['accept_alternative_notations'] = true;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = BibleTestQuestionResource::normalizeFormDataBeforeSave($data);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    
        protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}