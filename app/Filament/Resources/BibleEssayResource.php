<?php
// app/Filament/Resources/BibleEssayResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleEssayResource\Pages;
use App\Models\BibleEssay;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use BackedEnum;
use UnitEnum;

class BibleEssayResource extends Resource
{
    protected static ?string $model = BibleEssay::class;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-pencil-square';
    
    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';
    
    protected static ?string $navigationLabel = 'Проверка эссе';
    
    protected static ?string $pluralModelLabel = 'Эссе';
    
    protected static ?int $navigationSort = 4;
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Информация об эссе')
                ->schema([
                    TextInput::make('user.name')
                        ->label('Ученик')
                        ->disabled()
                        ->formatStateUsing(fn ($state, $record) => $record->user->full_name),
                    
                    TextInput::make('lesson.title')
                        ->label('Урок')
                        ->disabled()
                        ->formatStateUsing(fn ($state, $record) => $record->lesson->title),
                    
                    TextInput::make('question.question')
                        ->label('Вопрос')
                        ->disabled()
                        ->formatStateUsing(fn ($state, $record) => new HtmlString($record->question->question)),
                    
                    RichEditor::make('content')
                        ->label('Ответ ученика')
                        ->disabled()
                        ->columnSpanFull(),
                    
                    Select::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'На проверке',
                            'approved' => 'Одобрено',
                            'rejected' => 'Отклонено',
                        ])
                        ->required()
                        ->reactive(),
                    
                    TextInput::make('score')
                        ->label('Оценка (0-100)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->visible(fn ($get) => $get('status') !== 'pending'),
                    
                    RichEditor::make('teacher_feedback')
                        ->label('Отзыв учителя')
                        ->toolbarButtons(['bold', 'italic', 'underline'])
                        ->columnSpanFull()
                        ->visible(fn ($get) => $get('status') !== 'pending'),
                ])->columns(2),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('user.full_name')
                    ->label('Ученик')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('lesson.title')
                    ->label('Урок')
                    ->searchable()
                    ->sortable(),
                
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'На проверке',
                        'approved' => 'Одобрено',
                        'rejected' => 'Отклонено',
                        default => $state,
                    }),
                
                TextColumn::make('score')
                    ->label('Оценка')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                TextColumn::make('reviewed_at')
                    ->label('Проверено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'На проверке',
                        'approved' => 'Одобрено',
                        'rejected' => 'Отклонено',
                    ]),
                
                Tables\Filters\SelectFilter::make('lesson_id')
                    ->label('Урок')
                    ->relationship('lesson', 'title'),
            ])
            ->actions([
                EditAction::make()
                    ->label('Проверить')
                    ->icon('heroicon-o-eye'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBibleEssays::route('/'),
            'edit' => Pages\EditBibleEssay::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Эссе создаются только учениками
    }
}