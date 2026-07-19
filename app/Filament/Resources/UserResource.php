<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;
use BackedEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static UnitEnum|string|null $navigationGroup = 'Администрирование';
    
    protected static ?string $navigationLabel = 'Пользователи';
    
    protected static ?string $breadcrumb = 'Пользователи';
    
    protected static ?string $pluralModelLabel = 'Пользователи';
    
    protected static ?string $recordTitleAttribute = 'name';
    
    protected static ?int $navigationSort = 3;
    
    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getModel()::count();
            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        try {
            return auth()->user()?->hasRole('super_admin') ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Имя')
                ->required()
                ->maxLength(255),
                
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->afterStateUpdated(fn ($state, callable $set) => 
                    $set('email', strtolower($state))
                ),
                
            TextInput::make('password')
                ->label('Пароль')
                ->password()
                ->required(fn (string $context): bool => $context === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                ->maxLength(255)
                ->helperText('Оставьте пустым, чтобы не менять пароль'),
                
            Select::make('roles')
                ->label('Роли')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload()
                ->searchable()
                ->required(),
                
            Select::make('ministerCategories')
                ->label('Категории служения (для служителей)')
                ->multiple()
                ->relationship('ministerCategories', 'name')
                ->preload()
                ->searchable()
                ->visible(fn ($record) => $record?->hasRole('minister') ?? false),
                
            DateTimePicker::make('email_verified_at')
                ->label('Email подтверждён')
                ->native(false)
                ->displayFormat('d.m.Y H:i')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('roles.name')
                    ->label('Роли')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'editor' => 'info',
                        'user' => 'success',
                        default => 'gray',
                    }),
                    
                TextColumn::make('ministerCategories.name')
                    ->label('Категории служения')
                    ->badge()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->visible(fn ($record) => $record?->hasRole('minister') ?? false),
                    
                IconColumn::make('email_verified_at')
                    ->label('Подтверждён')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->getStateUsing(fn ($record): bool => $record->email_verified_at !== null),
                    
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Фильтр по ролям')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
                    
                TernaryFilter::make('email_verified_at')
                    ->label('Статус верификации')
                    ->nullable()
                    ->placeholder('Все пользователи')
                    ->trueLabel('Подтверждённые')
                    ->falseLabel('Неподтверждённые')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('email_verified_at'),
                        false: fn ($query) => $query->whereNull('email_verified_at'),
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}