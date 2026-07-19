<?php
// app/Filament/Resources/BiblePartyResource/Pages/PartyStudents.php

namespace App\Filament\Resources\BiblePartyResource\Pages;

use App\Filament\Resources\BiblePartyResource;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;

class PartyStudents extends Page
{
    protected static string $resource = BiblePartyResource::class;
    
    protected string $view = 'filament.resources.bible-party-resource.pages.party-students';
    
    public $record;
    
    public function mount($record): void
    {
        $this->record = \App\Models\BibleParty::findOrFail($record);
    }
    
    public function getTitle(): string
    {
        return "Участники группы: {$this->record->name}";
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Назад к группам')
                ->url(BiblePartyResource::getUrl())
                ->icon('heroicon-o-arrow-left'),
        ];
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->record->activeStudents())
            ->columns([
                TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable(),
                
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                
                TextColumn::make('phone')
                    ->label('Телефон'),
                
                TextColumn::make('joined_at')
                    ->label('Дата вступления')
                    ->dateTime('d.m.Y H:i'),
                
                TextColumn::make('course_progress')
                    ->label('Прогресс')
                    ->formatStateUsing(fn ($record) => $record->getCourseProgress($this->record->course_id)['percentage'] . '%'),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->label('Исключить')
                    ->color('danger')
                    ->icon('heroicon-o-user-minus')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $this->record->removeStudent($record->id);
                        $this->notify('success', 'Участник исключён из группы');
                    }),
            ]);
    }
}