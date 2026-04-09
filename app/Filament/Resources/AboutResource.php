<?php

namespace App\Filament\Resources;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\AboutResource\Pages;
use App\Models\About;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use BackedEnum;
use UnitEnum;

class AboutResource extends Resource
{
    protected static ?string $model = About::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static ?string $navigationLabel = 'Статьи';

    protected static ?string $breadcrumb = 'Статьи';

    protected static ?string $pluralModelLabel = 'Статьи';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rule('alpha_dash')
                    ->helperText('Автоматически генерируется из названия, но можно изменить'),

                Forms\Components\Textarea::make('description')
                    ->label('Коротко')
                    ->required()
                    ->rows(3)
                    ->autosize()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('content')
                    ->label('Основная статья')
                    ->required()
                    ->rows(3)
                    ->autosize()
                    ->columnSpanFull(),

                Forms\Components\Select::make('denomination_id')
                    ->label('Категория')
                    ->relationship('denomination', 'title')
                    ->required(),

                FileUpload::make('thumbnail')
                    ->label('Картинка')
                    ->image()
                    ->directory('abouts')
                    ->disk('public')
                    ->visibility('public')
                    ->imageEditor()
                    ->maxSize(5120)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->disk('public')
                    ->size(80)
                    ->circular(),

                TextColumn::make('title')
                    ->label('Название')
                    ->color('success'),

                TextColumn::make('denomination.title')
                    ->label('Категория')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->date('d.m.Y'),
            ])
            ->defaultSort('title', 'asc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                if ($record->thumbnail) {
                                    Storage::disk('public')->delete($record->thumbnail);
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
            'index'  => Pages\ListAbouts::route('/'),
            'create' => Pages\CreateAbout::route('/create'),
            'edit'   => Pages\EditAbout::route('/{record}/edit'),
        ];
    }
}