<?php
// app/Filament/Resources/BibleLessonCommentResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleLessonCommentResource\Pages;
use App\Models\BibleLessonComment;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use BackedEnum;
use UnitEnum;

class BibleLessonCommentResource extends Resource
{
    protected static ?string $model = BibleLessonComment::class;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';
    
    protected static ?string $navigationLabel = 'Комментарии';
    
    protected static ?string $pluralModelLabel = 'Комментарии';
    
    protected static ?int $navigationSort = 7;
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_approved', false)->count() ?: null;
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Информация о комментарии')
                ->schema([
                    TextInput::make('user.full_name')
                        ->label('Автор')
                        ->disabled()
                        ->formatStateUsing(fn ($state, $record) => $record->user->full_name),
                    
                    TextInput::make('lesson.title')
                        ->label('Урок')
                        ->disabled()
                        ->formatStateUsing(fn ($state, $record) => $record->lesson->title),
                    
                    TextInput::make('content')
                        ->label('Текст комментария')
                        ->disabled()
                        ->columnSpanFull()
                        ->formatStateUsing(fn ($state) => new HtmlString(nl2br(e($state))))],
                )->columns(2),
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
                    ->label('Автор')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('lesson.title')
                    ->label('Урок')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('content')
                    ->label('Комментарий')
                    ->limit(100)
                    ->formatStateUsing(fn ($state) => new HtmlString(nl2br(e($state)))),
                
                IconColumn::make('is_approved')
                    ->label('Одобрен')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Статус')
                    ->placeholder('Все комментарии')
                    ->trueLabel('Одобренные')
                    ->falseLabel('На модерации'),
                
                Tables\Filters\SelectFilter::make('lesson_id')
                    ->label('Урок')
                    ->relationship('lesson', 'title'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_approved)
                    ->action(function ($record) {
                        $record->approve(auth()->id());
                        Notification::make()
                            ->title('Комментарий одобрен')
                            ->success()
                            ->send();
                    }),
                
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => !$record->is_approved)
                    ->action(function ($record) {
                        $record->delete();
                        Notification::make()
                            ->title('Комментарий удалён')
                            ->warning()
                            ->send();
                    }),
                
                EditAction::make(),
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
            'index' => Pages\ListBibleLessonComments::route('/'),
            'edit' => Pages\EditBibleLessonComment::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Комментарии создаются только учениками
    }
}