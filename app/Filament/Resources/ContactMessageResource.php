<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;
use BackedEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationLabel = 'Сообщения';
    
    protected static ?string $pluralModelLabel = 'Сообщения';
    
    protected static ?string $modelLabel = 'Сообщение';
    
    protected static ?int $navigationSort = 2;
    
    protected static UnitEnum|string|null $navigationGroup = 'Управление';

    // 👇 ДОБАВЛЯЕМ ПРОВЕРКУ ПРАВ ДОСТУПА
    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        // Только супер-администраторы и администраторы могут просматривать сообщения
        return $user && ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('pastor'));
    }

    // 👇 ОГРАНИЧИВАЕМ ВИДИМОСТЬ ЗАПИСЕЙ (опционально)
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        // Если не супер-админ, показываем только сообщения для его роли (опционально)
        // Если нужно показывать все сообщения админам, можно ничего не менять
        
        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        // Показываем только количество непрочитанных сообщений
        $count = static::getModel()::where('is_read', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    /* ===================== FORM (Filament 4) ===================== */
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Имя')
                ->disabled()
                ->dehydrated(false),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->disabled()
                ->dehydrated(false),
            Select::make('user_id')
                ->label('Пользователь')
                ->relationship('user', 'name')
                ->disabled()
                ->dehydrated(false),
            TextInput::make('ip')
                ->label('IP адрес')
                ->disabled()
                ->dehydrated(false),
            DateTimePicker::make('created_at')
                ->label('Дата отправки')
                ->disabled()
                ->dehydrated(false),
            Textarea::make('message')
                ->label('Текст сообщения')
                ->disabled()
                ->dehydrated(false)
                ->rows(10)
                ->columnSpanFull(),
            Toggle::make('is_read')
                ->label('Прочитано')
                ->helperText('Отметьте, если сообщение прочитано')
                ->required(),
        ]);
    }

    /* ===================== TABLE (Filament 4) ===================== */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
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
                TextColumn::make('message')
                    ->label('Сообщение')
                    ->limit(50)
                    ->searchable(),
                IconColumn::make('is_read')
                    ->label('Прочитано')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('recipient_role')
                    ->label('Кому отправлено')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pastor' => 'Пастору',
                        'minister' => 'Служителям',
                        'pray' => 'Молитвенникам',
                        'super_admin' => 'Администратору',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('is_read')
                    ->label('Статус')
                    ->options([
                        '0' => 'Непрочитанные',
                        '1' => 'Прочитанные',
                    ]),
                SelectFilter::make('recipient_role')
                    ->label('Получатель')
                    ->options([
                        'pastor' => 'Пастору',
                        'minister' => 'Служителям',
                        'pray' => 'Молитвенникам',
                        'super_admin' => 'Администратору',
                    ]),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('От'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Редактировать')
                    ->color('warning'),
                Action::make('markAsRead')
                    ->label('Отметить прочитанным')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (ContactMessage $record) {
                        $record->markAsRead();
                    })
                    ->visible(fn (ContactMessage $record): bool => !$record->is_read),
                Action::make('markAsUnread')
                    ->label('Отметить непрочитанным')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (ContactMessage $record) {
                        $record->update(['is_read' => false]);
                    })
                    ->visible(fn (ContactMessage $record): bool => $record->is_read),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    Action::make('markAsReadBulk')
                        ->label('Отметить прочитанными')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records) {
                            $records->each->markAsRead();
                        }),
                ]),
            ]);
    }

    /* ===================== PAGES ===================== */

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }
}