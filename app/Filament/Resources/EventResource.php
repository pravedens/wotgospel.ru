<?php

namespace App\Filament\Resources;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\EventResource\Pages;
use App\Services\ImageOptimizer;
use App\Models\Event;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'События';
    
    protected static ?string $breadcrumb = 'События';
    
    protected static ?string $pluralModelLabel = 'События';
    
    protected static ?string $recordTitleAttribute = 'title';
    
    protected static ?int $navigationSort = 2;
    
    public static function getNavigationBadge(): ?string
    {
        $total = static::getModel()::count();
        $inCarousel = static::getModel()::where('show_in_carousel', true)->count();
        $limit = config('app.carousel.events_limit', 5);
    
        return "{$inCarousel}/{$limit} в карусели";
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $inCarousel = static::getModel()::where('show_in_carousel', true)->count();
        $limit = config('app.carousel.events_limit', 5);
    
        if ($inCarousel >= $limit) {
            return 'danger';
        }
    
        if ($inCarousel >= $limit * 0.7) {
            return 'warning';
        }
    
        return 'success';
    }

    /* ===================== FORM (Filament 4) ===================== */
    
   public static function form(Schema $schema): Schema
{
    return $schema->schema([
        TextInput::make('title')
            ->label('Название')
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
            
        DatePicker::make('startDate')
            ->label('Дата начала')
            ->required()
            ->native(false)
            ->displayFormat('d.m.Y'),
            
        TimePicker::make('startTime')
            ->label('Время начала')
            ->native(false)
            ->displayFormat('H:i'),
            
        Textarea::make('description')
            ->label('Кратко')
            ->required()
            ->rows(3)
            ->autosize()
            ->placeholder('Введите текст...')
            ->columnSpanFull(),
            
        Textarea::make('content')
            ->label('Подробно')
            ->required()
            ->rows(3)
            ->autosize()
            ->placeholder('Введите текст...')
            ->columnSpanFull(),
            
        FileUpload::make('thumbnail')
            ->label('Картинка')
            ->image()
            ->directory('events/thumbnails')
            ->disk('s3')
            ->visibility('public')
            ->imageEditor()
            ->maxSize(5120)
            ->saveUploadedFileUsing(function (UploadedFile $file, ?Model $record): string {
                $optimizedPath = ImageOptimizer::optimizeAndStore(
                    file: $file,
                    directory: 'events/thumbnails',
                    width: 1200,
                    height: 800,
                    quality: 85
                );
                
                if ($optimizedPath) {
                    if ($record && $record->thumbnail) {
                        Storage::disk('s3')->delete($record->thumbnail);
                        \Log::info('Old thumbnail deleted on update', ['path' => $record->thumbnail]);
                    }
                    return $optimizedPath;
                }
                
                \Log::warning('Image optimization failed in Filament, using original', [
                    'original_name' => $file->getClientOriginalName(),
                    'original_size' => $file->getSize()
                ]);
                
                return $file->store('events/thumbnails', 's3');
            })
            ->deleteUploadedFileUsing(function ($file, $record) {
                if ($record && $record->thumbnail) {
                    Storage::disk('s3')->delete($record->thumbnail);
                    \Log::info('Thumbnail deleted via deleteUploadedFileUsing', ['path' => $record->thumbnail]);
                }
            })
            ->columnSpanFull(),
            
        Textarea::make('info')
            ->label('Доп. информация')
            ->columnSpanFull()
            ->rows(3)
            ->autosize()
            ->placeholder('Введите текст...'),
            
        Select::make('color')
            ->label('Цвет в календаре')
            ->options([
                '#3b82f6' => 'Синий',
                '#ef4444' => 'Красный',
                '#10b981' => 'Зеленый',
                '#f59e0b' => 'Оранжевый',
                '#8b5cf6' => 'Фиолетовый',
                '#ec4899' => 'Розовый',
            ])
            ->default('#3b82f6')
            ->required(),
            
        Toggle::make('show_in_carousel')
            ->label('Показывать в карусели на главной')
            ->helperText('Если включено, событие будет отображаться в карусели ближайших событий')
            ->default(false)
            ->columnSpanFull(),
            
        Toggle::make('is_published')
            ->label('Опубликовано')
            ->helperText('Если выключено, событие увидят только администраторы')
            ->default(true)
            ->columnSpanFull(),
            
        Toggle::make('members_only')
            ->label('🔒 Только для членов церкви')
            ->visible(fn () => auth()->user()->hasRole(['redactorEvents', 'super_admin']))
            ->default(false),
            
        Toggle::make('ministers_only')
            ->label('👔 Только для служителей')
            ->helperText('Если включено, событие увидят только пользователи с ролью "minister"')
            ->visible(fn () => auth()->user()->hasRole(['redactorEvents', 'super_admin']))
            ->default(false),
            
        // ==================== КОНФЕРЕНЦИЯ ====================
        Toggle::make('is_conference')
            ->label('🎤 Это конференция')
            ->helperText('Если включено, можно добавить несколько служений (дней/сессий)')
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) {
                if (!$state) {
                    $set('conferenceServices', []);
                }
            })
            ->columnSpanFull(),

        Repeater::make('conferenceServices')
    ->label('Служения конференции')
    ->relationship('conferenceServices')  // <-- указываем имя отношения
    ->schema([
        DatePicker::make('service_date')
            ->label('Дата')
            ->required()
            ->native(false)
            ->displayFormat('d.m.Y'),
        
        TextInput::make('title')
            ->label('Название служения')
            ->required()
            ->maxLength(255),
        
        Textarea::make('description')
            ->label('Описание')
            ->rows(2)
            ->columnSpanFull(),
        
        TimePicker::make('start_time')
            ->label('Время начала')
            ->native(false)
            ->displayFormat('H:i'),
        
        TextInput::make('speaker')
            ->label('Спикер')
            ->maxLength(255),
        
        TextInput::make('capacity')
            ->label('Максимум участников')
            ->numeric()
            ->default(0)
            ->minValue(0)
            ->helperText('0 = без ограничений'),
        
        TextInput::make('order')
            ->label('Порядок отображения')
            ->numeric()
            ->default(0),
    ])
    ->defaultItems(1)
    ->addActionLabel('➕ Добавить служение')
    ->reorderableWithDragAndDrop()
    ->reorderable()
    ->visible(fn ($get) => $get('is_conference'))
    ->columnSpanFull(),
    ]);
}

    /* ===================== TABLE (Filament 4) ===================== */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Картинка')
                    ->disk('s3')
                    ->size(80)
                    ->circular(),
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('startDate')
                    ->label('Дата')
                    ->date('d.m.Y'),
                TextColumn::make('startTime')
                    ->label('Время')
                    ->date('H:i'),
                IconColumn::make('show_in_carousel')
                    ->label('В карусели')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                IconColumn::make('is_published')
                    ->label('Опубликовано')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                IconColumn::make('members_only')
                    ->label('Только члены')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                IconColumn::make('ministers_only')
                    ->label('Только служители')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('purple')
                    ->falseColor('gray'),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Предстоящие')
                    ->query(fn ($query) => $query->upcoming()),
                    
                Filter::make('past')
                    ->label('Прошедшие')
                    ->query(fn ($query) => $query->past()),
                    
                TernaryFilter::make('show_in_carousel')
                    ->label('В карусели'),
                    
                TernaryFilter::make('is_published')
                    ->label('Статус публикации')
                    ->placeholder('Все события')
                    ->trueLabel('Опубликованные')
                    ->falseLabel('Неопубликованные'),
                    
                TernaryFilter::make('members_only')
                    ->label('Только для членов'),
                    
                TernaryFilter::make('ministers_only')
                    ->label('Только для служителей')
                    ->placeholder('Все события')
                    ->trueLabel('Только для служителей')
                    ->falseLabel('Не для служителей'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (Event $record) {
                        if ($record->thumbnail) {
                            Storage::disk('s3')->delete($record->thumbnail);
                            \Log::info('Thumbnail deleted on record delete', ['path' => $record->thumbnail]);
                        }
                        $record->delete();
                        
                        Notification::make()
                            ->title('Событие удалено')
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
                                    Storage::disk('s3')->delete($record->thumbnail);
                                    \Log::info('Thumbnail deleted on bulk delete', ['path' => $record->thumbnail]);
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}