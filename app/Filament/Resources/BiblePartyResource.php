<?php
// app/Filament/Resources/BiblePartyResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\BiblePartyResource\Pages;
use App\Models\BibleParty;
use App\Models\BibleCourse;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use BackedEnum;
use UnitEnum;

class BiblePartyResource extends Resource
{
    protected static ?string $model = BibleParty::class;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    
    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';
    
    protected static ?string $navigationLabel = 'Группы (Party)';
    
    protected static ?string $pluralModelLabel = 'Группы';
    
    protected static ?int $navigationSort = 6;
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Основная информация')
                ->schema([
                    TextInput::make('name')
                        ->label('Название группы')
                        ->required()
                        ->maxLength(255),
                    
                    RichEditor::make('description')
                        ->label('Описание')
                        ->toolbarButtons(['bold', 'italic'])
                        ->columnSpanFull(),
                    
                    Select::make('course_id')
                        ->label('Курс')
                        ->options(BibleCourse::where('is_published', true)->pluck('title', 'id'))
                        ->required()
                        ->searchable(),
                    
                    Select::make('leader_id')
                        ->label('Лидер группы')
                        ->options(User::role(['teacher', 'group_leader', 'pastor'])->get()->pluck('full_name', 'id'))
                        ->searchable()
                        ->helperText('Может отмечать посещаемость и управлять группой'),
                    
                    TextInput::make('join_code')
                        ->label('Код для вступления')
                        ->maxLength(10)
                        ->unique(BibleParty::class, 'join_code', ignoreRecord: true)
                        ->helperText('Оставьте пустым для автоматической генерации'),
                    
                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),
                ])->columns(2),
            
            Section::make('Расписание встреч')
                ->schema([
                    Select::make('meeting_day')
                        ->label('День недели')
                        ->options([
                            'monday' => 'Понедельник',
                            'tuesday' => 'Вторник',
                            'wednesday' => 'Среда',
                            'thursday' => 'Четверг',
                            'friday' => 'Пятница',
                            'saturday' => 'Суббота',
                            'sunday' => 'Воскресенье',
                        ])
                        ->nullable(),
                    
                    TextInput::make('meeting_time')
                        ->label('Время встречи')
                        ->type('time')
                        ->nullable(),
                    
                    TextInput::make('zoom_link')
                        ->label('Ссылка на Zoom')
                        ->url()
                        ->maxLength(500)
                        ->nullable(),
                    
                    TextInput::make('max_students')
                        ->label('Максимум студентов')
                        ->numeric()
                        ->default(30)
                        ->minValue(1),
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
                
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('course.title')
                    ->label('Курс')
                    ->searchable(),
                
                TextColumn::make('leader.full_name')
                    ->label('Лидер')
                    ->searchable(),
                
                TextColumn::make('join_code')
                    ->label('Код')
                    ->copyable()
                    ->copyMessage('Код скопирован'),
                
                TextColumn::make('active_students_count')
                    ->label('Участников')
                    ->counts('activeStudents')
                    ->sortable(),
                
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Все группы')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
                
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Курс')
                    ->options(BibleCourse::pluck('title', 'id')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('regenerate_code')
                    ->label('Новый код')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function ($record) {
                        $record->regenerateJoinCode();
                        \Filament\Notifications\Notification::make()
                            ->title('Код обновлён')
                            ->body("Новый код: {$record->join_code}")
                            ->success()
                            ->send();
                    }),
                Action::make('students')
                    ->label('Участники')
                    ->icon('heroicon-o-users')
                    ->url(fn ($record) => BiblePartyResource::getUrl('students', ['record' => $record])),
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
            'index' => Pages\ListBibleParties::route('/'),
            'create' => Pages\CreateBibleParty::route('/create'),
            'edit' => Pages\EditBibleParty::route('/{record}/edit'),
            'students' => Pages\PartyStudents::route('/{record}/students'),
        ];
    }
}