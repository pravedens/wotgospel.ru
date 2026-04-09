<?php

namespace App\Filament\Resources;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\LiveStreamResource\Pages;
use App\Models\LiveStream;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class LiveStreamResource extends Resource
{
    protected static ?string $model = LiveStream::class;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-video-camera';
    
    protected static ?string $navigationLabel = 'Трансляции';
    
    protected static ?string $pluralModelLabel = 'Трансляции';
    
    protected static ?int $navigationSort = 4;
    
    /* ===================== FORM (Filament 4) ===================== */
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('platform')
                ->label('Платформа')
                ->options([
                    'rutube' => 'Rutube',
                    'youtube' => 'YouTube',
                    'vk' => 'VK Видео',
                ])
                ->required()
                ->live()
                ->helperText('Выберите платформу для вставки ссылки'),
            
            TextInput::make('embed_url')
                ->label('Ссылка на видео')
                ->required()
                ->url()
                ->placeholder(function ($get) {
                    $platform = $get('platform');
                    return match($platform) {
                        'rutube' => 'https://rutube.ru/video/... или https://rutube.ru/play/embed/...',
                        'youtube' => 'https://youtu.be/... или https://www.youtube.com/watch?v=...',
                        'vk' => 'https://vk.com/video-123456_7891011',
                        default => 'Вставьте ссылку на видео'
                    };
                })
                ->helperText(function ($get) {
                    $platform = $get('platform');
                    return match($platform) {
                        'rutube' => 'Можно вставить любую ссылку на Rutube: https://rutube.ru/video/... или https://rutube.ru/play/embed/...',
                        'youtube' => 'Можно вставить ссылку из адресной строки или короткую ссылку youtu.be/...',
                        'vk' => 'Скопируйте ссылку на видео из VK',
                        default => 'Вставьте полную ссылку на видео'
                    };
                })
                ->afterStateUpdated(function ($state, Set $set) {
                    if (str_contains($state, 'rutube.ru')) {
                        $set('platform', 'rutube');
                    } elseif (str_contains($state, 'youtube.com') || str_contains($state, 'youtu.be')) {
                        $set('platform', 'youtube');
                    } elseif (str_contains($state, 'vk.com')) {
                        $set('platform', 'vk');
                    }
                }),
            
            Toggle::make('is_active')
                ->label('Активна сейчас')
                ->helperText('Включите, если трансляция идет прямо сейчас')
                ->default(false),
        ]);
    }
    
    /* ===================== TABLE (Filament 4) ===================== */
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('platform')
                    ->label('Платформа')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'rutube' => 'Rutube',
                        'youtube' => 'YouTube',
                        'vk' => 'VK',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'rutube' => 'purple',
                        'youtube' => 'danger',
                        'vk' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                TextColumn::make('scheduled_start')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('platform')
                    ->options([
                        'rutube' => 'Rutube',
                        'youtube' => 'YouTube',
                        'vk' => 'VK',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Активность'),
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
    
    /* ===================== PAGES ===================== */
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiveStreams::route('/'),
            'create' => Pages\CreateLiveStream::route('/create'),
            'edit' => Pages\EditLiveStream::route('/{record}/edit'),
        ];
    }
}