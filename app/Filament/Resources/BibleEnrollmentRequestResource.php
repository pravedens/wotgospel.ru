<?php
// app/Filament/Resources/BibleEnrollmentRequestResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleEnrollmentRequestResource\Pages;
use App\Models\BibleEnrollmentRequest;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class BibleEnrollmentRequestResource extends Resource
{
    protected static ?string $model = BibleEnrollmentRequest::class;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-plus';
    
    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';
    
    protected static ?string $navigationLabel = 'Заявки на обучение';
    
    protected static ?string $pluralModelLabel = 'Заявки на обучение';
    
    protected static ?int $navigationSort = 5;
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Информация о заявке')
                ->schema([
                    Select::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'На рассмотрении',
                            'approved' => 'Одобрена',
                            'rejected' => 'Отклонена',
                        ])
                        ->required()
                        ->reactive(),
                    
                    Textarea::make('notes')
                        ->label('Дополнительная информация')
                        ->rows(3)
                        ->disabled()
                        ->visible(fn ($record) => $record && $record->notes),
                    
                    Textarea::make('admin_notes')
                        ->label('Заметки администратора')
                        ->rows(3)
                        ->placeholder('Причина одобрения/отклонения...'),
                ])->columns(1),
            
            Section::make('Анкета ученика')
                ->schema([
                    TextInput::make('city')
                        ->label('Город')
                        ->maxLength(255),
                    TextInput::make('church_name')
                        ->label('Церковь')
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->maxLength(20),
                    DatePicker::make('birth_date')
                        ->label('Дата рождения')
                        ->displayFormat('d.m.Y'),
                    Textarea::make('about')
                        ->label('О себе')
                        ->rows(3),
                    Select::make('marital_status')
                        ->label('Семейное положение')
                        ->options([
                            'single' => 'Холост/Не замужем',
                            'married' => 'В браке',
                            'divorced' => 'Разведён(а)',
                            'widowed' => 'Вдова/Вдовец',
                        ]),
                    Select::make('gender')
                        ->label('Пол')
                        ->options([
                            'male' => 'Мужской',
                            'female' => 'Женский',
                        ]),
                    TextInput::make('ministry')
                        ->label('Служение в церкви')
                        ->maxLength(255),
                    Textarea::make('bible_courses_experience')
                        ->label('Опыт прохождения библейских курсов')
                        ->rows(3),
                    Textarea::make('learning_expectations')
                        ->label('Ожидания от обучения')
                        ->rows(3),
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
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                
                TextColumn::make('user.phone')
                    ->label('Телефон')
                    ->searchable(),
                
                TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('church_name')
                    ->label('Церковь')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'На рассмотрении',
                        'approved' => 'Одобрена',
                        'rejected' => 'Отклонена',
                        default => $state,
                    }),
                
                TextColumn::make('created_at')
                    ->label('Дата подачи')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                TextColumn::make('reviewed_at')
                    ->label('Дата обработки')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'approved' => 'Одобрена',
                        'rejected' => 'Отклонена',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->approve(auth()->id());
                        Notification::make()
                            ->title('Заявка одобрена')
                            ->body("Пользователю {$record->user->full_name} назначена роль студента")
                            ->success()
                            ->send();
                    }),
                
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->reject(auth()->id());
                        Notification::make()
                            ->title('Заявка отклонена')
                            ->body("Заявка пользователя {$record->user->full_name} отклонена")
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
            'index' => Pages\ListBibleEnrollmentRequests::route('/'),
            'edit' => Pages\EditBibleEnrollmentRequest::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
}