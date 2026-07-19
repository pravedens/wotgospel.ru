<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MinisterCategoryResource\Pages;
use App\Models\MinisterCategory;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

class MinisterCategoryResource extends Resource
{
    protected static ?string $model = MinisterCategory::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';
    
    protected static UnitEnum|string|null $navigationGroup = 'Служители';
    
    protected static ?string $navigationLabel = 'Категории служителей';
    
    protected static ?string $breadcrumb = 'Категории служителей';
    
    protected static ?string $pluralModelLabel = 'Категории служителей';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        try {
            return static::getModel()::count() ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => 
                    $set('slug', Str::slug($state))
                ),
                
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Автоматически генерируется из названия'),
                
            TextInput::make('icon')
                ->label('Иконка (эмодзи)')
                ->maxLength(10)
                ->placeholder('🙏, 🎸, 👨‍👩‍👧‍👦')
                ->helperText('Введите эмодзи для отображения в карточке'),
                
            TextInput::make('color')
                ->label('Цвет (HEX)')
                ->type('color')
                ->placeholder('#10b981')
                ->helperText('Цвет фона для категории в карточке'),
                
            TextInput::make('sort_order')
                ->label('Порядок сортировки')
                ->numeric()
                ->default(0)
                ->integer()
                ->helperText('Меньше число — выше в списке'),
                
            Textarea::make('description')
                ->label('Описание')
                ->maxLength(65535)
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('icon')
                    ->label('Иконка')
                    ->searchable(),
                    
                TextColumn::make('color')
                    ->label('Цвет')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ?? '—'),
                    
                TextColumn::make('users_count')
                    ->label('Служителей')
                    ->counts('users')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMinisterCategories::route('/'),
            'create' => Pages\CreateMinisterCategory::route('/create'),
            'edit' => Pages\EditMinisterCategory::route('/{record}/edit'),
        ];
    }
}