<?php

namespace App\Filament\Resources;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\BibleResource\Pages;
use App\Models\Bible;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

class BibleResource extends Resource
{
    protected static ?string $model = Bible::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?string $navigationLabel = 'Стихи Библии';
    
    protected static ?string $breadcrumb = 'Стихи Библии';
    
    protected static ?string $pluralModelLabel = 'Стихи Библии';
    
    protected static ?string $recordTitleAttribute = 'title';
    
    protected static ?int $navigationSort = 3;
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /* ===================== FORM (Filament 4) ===================== */
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            DatePicker::make('date')
                ->label('Дата публикации')
                ->displayFormat('d.m.Y')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Стих будет показываться в этот день'),
                
            TextInput::make('title')
                ->label('Ссылка (например: Иоанна 3:16)')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, $state) {
                    $set('slug', Str::slug($state));
                }),
                
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->rule('alpha_dash')
                ->helperText('Автоматически генерируется, но можно изменить'),
                
            Textarea::make('description')
                ->label('Текст стиха')
                ->required()
                ->rows(4)
                ->autosize()
                ->placeholder('Введите текст стиха...')
                ->columnSpanFull(),
        ]);
    }

    /* ===================== TABLE (Filament 4) ===================== */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Ссылка')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Текст стиха')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    /* ===================== HELPERS ===================== */

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }
    
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }
    
        return $data;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBibles::route('/'),
            'create' => Pages\CreateBible::route('/create'),
            'edit' => Pages\EditBible::route('/{record}/edit'),
        ];
    }
}