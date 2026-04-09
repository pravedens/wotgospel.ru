<?php

namespace App\Filament\Resources;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\DenominationResource\Pages;
use App\Models\Denomination;
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

class DenominationResource extends Resource
{
    protected static ?string $model = Denomination::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Категория';
    
    protected static ?string $breadcrumb = 'Категория';
    
    protected static ?string $pluralModelLabel = 'Категории';
    
    protected static UnitEnum|string|null $navigationGroup = 'О нас';
    
    protected static ?string $recordTitleAttribute = 'title';
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /* ===================== FORM (Filament 4) ===================== */
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')
                ->label('категория')
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
                ->helperText('Автоматически генерируется из названия, но можно изменить'),
        ]);
    }

    /* ===================== TABLE (Filament 4) ===================== */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('категория')
                    ->searchable(),
            ])
            ->defaultSort('title', 'asc')
            ->filters([])
            ->actions([
                EditAction::make(),
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

    /* ===================== RELATIONS & PAGES ===================== */

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDenominations::route('/'),
            'create' => Pages\CreateDenomination::route('/create'),
            'edit' => Pages\EditDenomination::route('/{record}/edit'),
        ];
    }
}