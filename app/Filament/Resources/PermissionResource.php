<?php

namespace App\Filament\Resources;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use UnitEnum;
use BackedEnum;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static UnitEnum|string|null $navigationGroup = 'Администрирование';

    protected static ?int $navigationSort = 2;

    /* ===================== FORM (Filament 4) ===================== */
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Название разрешения')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Формат: действие_ресурс (например: view_event, create_post)'),
                
            Select::make('roles')
                ->label('Роли')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload()
                ->searchable(),
                
            TextInput::make('guard_name')
                ->label('Guard')
                ->default('web')
                ->required()
                ->maxLength(255),
        ]);
    }

    /* ===================== TABLE (Filament 4) ===================== */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('name')
                    ->label('Разрешение')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => str($state)
                        ->replace('_', ' ')
                        ->title()),
                        
                TextColumn::make('roles.name')
                    ->label('Роли')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'editor' => 'info',
                        'user' => 'success',
                        default => 'gray',
                    })
                    ->separator(',')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(),
                    
                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->striped()
            ->filters([])
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

    /* ===================== PAGES & NAVIGATION ===================== */

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'view' => Pages\ViewPermission::route('/{record}'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        // Только суперадмин видит раздел Permissions
        try {
            return auth()->user()?->hasRole('super_admin') ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }
}