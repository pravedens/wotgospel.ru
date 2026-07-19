<?php

namespace App\Filament\Resources;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\EventRegistrationResource\Pages;
use App\Models\EventRegistration;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use BackedEnum;

class EventRegistrationResource extends Resource
{
    protected static ?string $model = EventRegistration::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Регистрации';
    
    protected static ?string $breadcrumb = 'Регистрации';
    
    protected static ?string $pluralModelLabel = 'Регистрации';
    
    protected static ?int $navigationSort = 3;
    
    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        return $pending > 0 ? (string) $pending : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        if ($pending > 0) {
            return 'warning';
        }
        return 'success';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('managerEvents') || $user->hasRole('super_admin') || $user->hasRole('admin'));
    }

    /* ===================== FORM ===================== */
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('event_id')
                ->label('Событие')
                ->relationship('event', 'title')
                ->required()
                ->searchable()
                ->preload(),
                
            Select::make('user_id')
                ->label('Пользователь')
                ->relationship('user', 'name')
                ->required()
                ->searchable()
                ->preload(),
                
            Select::make('status')
                ->label('Статус')
                ->options([
                    'pending' => 'Ожидает',
                    'confirmed' => 'Подтверждена',
                    'cancelled' => 'Отменена',
                    'waiting' => 'Лист ожидания',
                ])
                ->required()
                ->default('pending'),
            
            // ✅ Выбранные служения (используем аксессор из модели)
            Select::make('selected_service_ids')
                ->label('Выбранные служения')
                ->multiple()
                ->options(function ($record) {
                    if (!$record || !$record->event || !$record->event->is_conference) {
                        return [];
                    }
                    
                    // Загружаем служения
                    $record->load('event.conferenceServices');
                    $services = $record->event->conferenceServices;
                    
                    if ($services->isEmpty()) {
                        return [];
                    }
                    
                    $options = [];
                    foreach ($services as $service) {
                        $date = $service->service_date ? $service->service_date->format('d.m.Y') : '';
                        $time = $service->start_time ? substr($service->start_time, 0, 5) : '';
                        $options[$service->id] = trim("{$date} {$time} - {$service->title}");
                    }
                    
                    return $options;
                })
                ->default(function ($record) {
                    // Используем аксессор из модели
                    return $record ? $record->selected_service_ids_array : [];
                })
                ->disabled()
                ->columnSpanFull(),
                
            Textarea::make('admin_notes')
                ->label('Заметки администратора')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    /* ===================== TABLE ===================== */

    public static function table(Table $table): Table
{
    return $table
        ->query(EventRegistration::query()->with(['event.conferenceServices', 'user']))
        ->columns([
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('event.title')->label('Событие')->searchable()->sortable(),
            TextColumn::make('user.name')->label('Пользователь')->searchable(),
            TextColumn::make('user.email')->label('Email')->searchable(),
            
            // ✅ Выбранные служения (прямой запрос)
            TextColumn::make('selected_services_raw')
                ->label('Выбранные служения')
                ->state(function ($record) {
                    $ids = $record->selected_service_ids ?? [];
                    if (empty($ids)) {
                        return '-';
                    }
                    
                    $services = $record->event->conferenceServices ?? collect();
                    
                    $result = [];
                    foreach ($ids as $id) {
                        $service = $services->firstWhere('id', $id);
                        if ($service) {
                            $date = $service->service_date ? $service->service_date->format('d.m.Y') : 'дата не указана';
                            $time = $service->start_time ? substr($service->start_time, 0, 5) : '';
                            $result[] = "{$date} {$time}";
                        } else {
                            $result[] = "ID:{$id} (не найдено)";
                        }
                    }
                    
                    return implode("\n", $result);
                })
                ->html()
                ->wrap(),
                
            TextColumn::make('status')
                ->label('Статус')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'cancelled' => 'danger',
                    'waiting' => 'info',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'pending' => 'Ожидает',
                    'confirmed' => 'Подтверждена',
                    'cancelled' => 'Отменена',
                    'waiting' => 'Лист ожидания',
                    default => $state,
                }),
                    
                TextColumn::make('created_at')
                    ->label('Дата регистрации')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждена',
                        'cancelled' => 'Отменена',
                        'waiting' => 'Лист ожидания',
                    ]),
                    
                SelectFilter::make('event_id')
                    ->relationship('event', 'title')
                    ->label('Событие')
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('confirm')
                    ->label('Подтвердить')
                    ->color('success')
                    ->action(function (EventRegistration $record) {
                        $record->update(['status' => 'confirmed', 'processed_at' => now()]);
                        Notification::make()
                            ->title('Регистрация подтверждена')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (EventRegistration $record) => $record->status === 'pending'),
                    
                Action::make('reject')
                    ->label('Отклонить')
                    ->color('danger')
                    ->action(function (EventRegistration $record) {
                        $record->update(['status' => 'cancelled', 'processed_at' => now()]);
                        Notification::make()
                            ->title('Регистрация отклонена')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (EventRegistration $record) => $record->status === 'pending'),
                    
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->delete();
                            }
                            Notification::make()
                                ->title('Регистрации удалены')
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
            'index' => Pages\ListEventRegistrations::route('/'),
            'create' => Pages\CreateEventRegistration::route('/create'),
            'edit' => Pages\EditEventRegistration::route('/{record}/edit'),
        ];
    }
}