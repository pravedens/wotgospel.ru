<?php
// app/Filament/Resources/BibleStudentResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleStudentResource\Pages;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class BibleStudentResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';
    
    protected static ?string $navigationLabel = 'Студенты';
    
    protected static ?string $pluralModelLabel = 'Студенты';
    
    protected static ?int $navigationSort = 8;
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->role('student');
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                
                TextColumn::make('city')
                    ->label('Город')
                    ->searchable(),
                
                TextColumn::make('church_name')
                    ->label('Церковь')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('marital_status')
                    ->label('Семейное положение')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'single' => 'Холост/Не замужем',
                        'married' => 'В браке',
                        'divorced' => 'Разведён(а)',
                        'widowed' => 'Вдова/Вдовец',
                        default => '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('gender')
                    ->label('Пол')
                    ->formatStateUsing(fn ($state) => $state === 'male' ? 'Мужской' : ($state === 'female' ? 'Женский' : '—'))
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('ministry')
                    ->label('Служение')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                BadgeColumn::make('roles.name')
                    ->label('Роли')
                    ->badge(),
                
                TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('city')
                    ->label('Город')
                    ->options(fn () => User::role('student')->pluck('city', 'city')->filter()->toArray()),
                
                Tables\Filters\SelectFilter::make('church_name')
                    ->label('Церковь')
                    ->options(fn () => User::role('student')->pluck('church_name', 'church_name')->filter()->toArray()),
                
                Tables\Filters\SelectFilter::make('marital_status')
                    ->label('Семейное положение')
                    ->options([
                        'single' => 'Холост/Не замужем',
                        'married' => 'В браке',
                        'divorced' => 'Разведён(а)',
                        'widowed' => 'Вдова/Вдовец',
                    ]),
                
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Пол')
                    ->options([
                        'male' => 'Мужской',
                        'female' => 'Женский',
                    ]),
            ])
            ->actions([
                Action::make('progress')
                    ->label('Прогресс')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record) => BibleStudentResource::getUrl('progress', ['record' => $record])),
                
                EditAction::make(),
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
            'index' => Pages\ListBibleStudents::route('/'),
            'progress' => Pages\StudentProgress::route('/{record}/progress'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
}