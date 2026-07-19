<?php
// app/Filament/Resources/FriendResource.php

namespace App\Filament\Resources;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\FriendResource\Pages;
use App\Models\Friend;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

class FriendResource extends Resource
{
    protected static ?string $model = Friend::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Дружественные церкви';
    
    protected static ?string $breadcrumb = 'Дружественные церкви';
    
    protected static ?string $pluralModelLabel = 'Дружественные церкви';
    
    protected static ?string $recordTitleAttribute = 'title';
    
    protected static ?int $navigationSort = 20;
    
    public static function getNavigationBadge(): ?string
    {
        $total = static::getModel()::count();
        $active = static::getModel()::where('is_active', true)->count();
        
        return "{$active}/{$total}";
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $active = static::getModel()::where('is_active', true)->count();
        $total = static::getModel()::count();
        
        if ($total === 0) {
            return 'danger';
        }
        
        if ($active === 0) {
            return 'danger';
        }
        
        if ($active < $total) {
            return 'warning';
        }
        
        return 'success';
    }

    /* ===================== FORM (Filament 4) ===================== */
    
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                    TextInput::make('title')
                        ->label('Название церкви')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('slug', Str::slug($state));
                        }),
                        
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rule('alpha_dash')
                        ->helperText('Автоматически генерируется из названия, но можно изменить'),
                        
                    TextInput::make('link')
                        ->label('Ссылка на сайт')
                        ->url()
                        ->maxLength(255)
                        ->helperText('https://example.com'),
                        
                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(3)
                        ->autosize()
                        ->placeholder('Введите описание...')
                        ->columnSpanFull(),
                        
                    FileUpload::make('thumbnail')
                        ->label('Логотип')
                        ->image()
                        ->directory('friends')
                        ->disk('s3')
                        ->visibility('public')
                        ->imageEditor()
                        ->imageResizeMode('cover')
                        ->imageResizeTargetWidth('200')
                        ->imageResizeTargetHeight('200')
                        ->maxSize(1024)
                        ->helperText('Рекомендуемый размер: 200x200px')
                        ->columnSpanFull(),
                        
                    TextInput::make('sort_order')
                        ->label('Порядок сортировки')
                        ->numeric()
                        ->default(0)
                        ->helperText('Чем меньше число, тем выше в списке'),
                        
                    Toggle::make('is_active')
                        ->label('Активно')
                        ->helperText('Если выключено, церковь не будет отображаться на сайте')
                        ->default(true)
                        ->columnSpanFull(),
            
        ]);
    }

    /* ===================== TABLE (Filament 4) ===================== */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Логотип')
                    ->disk('s3')
                    ->size(60)
                    ->circular(),
                    
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('link')
                    ->label('Ссылка')
                    ->limit(40)
                    ->sortable(),
                    
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                    
                IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (Friend $record) {
                        if ($record->thumbnail) {
                            \Illuminate\Support\Facades\Storage::disk('s3')->delete($record->thumbnail);
                            \Log::info('Friend thumbnail deleted', ['path' => $record->thumbnail]);
                        }
                        $record->delete();
                        
                        Notification::make()
                            ->title('Запись удалена')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                if ($record->thumbnail) {
                                    \Illuminate\Support\Facades\Storage::disk('s3')->delete($record->thumbnail);
                                }
                                $record->delete();
                            }
                            
                            Notification::make()
                                ->title('Записи удалены')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
    
    /* ===================== RELATIONS & PAGES ===================== */

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFriends::route('/'),
            'create' => Pages\CreateFriend::route('/create'),
            'edit' => Pages\EditFriend::route('/{record}/edit'),
        ];
    }
}