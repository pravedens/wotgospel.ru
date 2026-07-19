<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleThemeResource\Pages;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Models\BibleTheme;
use App\Models\BibleCourse;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use BackedEnum;
use UnitEnum;

class BibleThemeResource extends Resource
{
    protected static ?string $model = BibleTheme::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-folder';
    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';
    protected static ?string $navigationLabel = 'Темы';
    protected static ?int $navigationSort = 3;
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('course_id')
                ->label('Курс')
                ->options(BibleCourse::where('is_published', true)->pluck('title', 'id'))
                ->required()
                ->searchable()
                ->reactive(),
            
            TextInput::make('title')
                ->label('Название темы')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, $state, callable $set) => 
                    $operation === 'create' ? $set('slug', Str::slug($state)) : null
                ),
            
            TextInput::make('slug')
                ->label('URL-адрес')
                ->required()
                ->maxLength(255)
                ->unique(BibleTheme::class, 'slug', ignoreRecord: true),
            
            Textarea::make('description')
                ->label('Описание')
                ->rows(3),
            
            TextInput::make('order')
                ->label('Порядок')
                ->numeric()
                ->default(0),
                
            Select::make('teacher_id')
    ->label('Преподаватель темы')
    ->options(function () {
        return User::role('teacher')->get()->mapWithKeys(function ($user) {
            return [$user->id => $user->last_name . ' ' . $user->name];
        });
    })
    ->searchable()
    ->nullable(),
            
            Toggle::make('is_published')
                ->label('Опубликовано')
                ->default(true),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')->label('№')->sortable(),
                TextColumn::make('course.title')->label('Курс')->searchable(),
                TextColumn::make('title')->label('Название')->searchable(),
                TextColumn::make('lessons_count')->label('Уроков')->counts('lessons'),
                IconColumn::make('is_published')->label('Опубл.')->boolean(),
            ])
            ->defaultSort('order')
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')->label('Курс')->options(BibleCourse::pluck('title', 'id')),
            ])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBibleTheme::route('/'),
            'create' => Pages\CreateBibleTheme::route('/create'),
            'edit' => Pages\EditBibleTheme::route('/{record}/edit'),
        ];
    }
}