<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use BackedEnum;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Публикации';
    
    protected static ?string $pluralModelLabel = 'Публикации';
    
    protected static UnitEnum|string|null $navigationGroup = 'Проповеди';
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')
                ->label('Название')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug($state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            DatePicker::make('created_at')->label('Дата')->required()->displayFormat('d.m.Y'),
            Select::make('category_id')->label('Спикер')->relationship('category', 'title')->required(),
            Select::make('conference_id')->label('Мероприятие')->relationship('conference', 'title')->required(),
            Select::make('group_id')->label('Год')->relationship('group', 'title')->required(),
            Textarea::make('description')->label('Кратко')->rows(3),
            Textarea::make('content')->label('Основное')->rows(3),
            
            // Медиафайлы
            FileUpload::make('thumbnail')->label('Изображение')->image()->directory('posts/thumbnails')->disk('s3'),
            FileUpload::make('audio_file')->label('Аудио файл')->directory('posts/audio')->disk('s3')->maxSize(204800)->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a']),
            FileUpload::make('text_file')->label('Текстовый файл')->directory('posts/text')->disk('s3'),
            
            // Ссылки на видео
            TextInput::make('youtube')->label('YouTube')->maxLength(255)->url(),
            TextInput::make('rutube')->label('Rutube')->maxLength(255)->url(),
            TextInput::make('dzen')->label('Дзен')->maxLength(255)->url(),
            TextInput::make('vkVideo')->label('VK Видео')->maxLength(255)->url(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')->disk('s3')->size(80)->circular(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('category.title')->badge(),
                TextColumn::make('created_at')->date('d.m.Y'),
            ])
            ->defaultSort('created_at', 'desc')
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
                                    Storage::disk('s3')->delete($record->thumbnail);
                                }
                                $record->delete();
                            }
                            Notification::make()->title('Записи удалены')->success()->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}