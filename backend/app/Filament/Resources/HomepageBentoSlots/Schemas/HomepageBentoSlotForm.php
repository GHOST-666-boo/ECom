<?php

namespace App\Filament\Resources\HomepageBentoSlots\Schemas;

use App\Services\ImageStorageService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HomepageBentoSlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slot_key')
                    ->label('Slot Key')
                    ->disabled()
                    ->required()
                    ->helperText('Predefined identifier for homepage layout'),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                TextInput::make('subtitle')
                    ->maxLength(255)
                    ->nullable(),

                TextInput::make('badge')
                    ->maxLength(50)
                    ->nullable()
                    ->placeholder('e.g., Signature, New Arrival'),

                Select::make('theme')
                    ->options([
                        'light' => 'Light',
                        'dark' => 'Dark',
                        'gradient' => 'Gradient',
                    ])
                    ->required()
                    ->default('light'),

                TextInput::make('icon')
                    ->label('Icon / Emoji')
                    ->maxLength(10)
                    ->nullable()
                    ->helperText('Optional emoji or icon character (primarily used for Slot 3, e.g. ⚒)'),

                FileUpload::make('image')
                    ->label('Background Image')
                    ->image()
                    ->disk('r2')
                    ->directory('bento')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->nullable()
                    ->helperText('Upload background image (max 2MB, JPEG/PNG/WebP). EXIF metadata will be stripped automatically.')
                    ->saveUploadedFileUsing(function ($file) {
                        $imageService = app(ImageStorageService::class);
                        return $imageService->uploadImage($file, 'bento');
                    }),

                Select::make('link_type')
                    ->label('Link Target Type')
                    ->options([
                        'none' => 'No Link',
                        'category' => 'Link to Category',
                        'product' => 'Link to Product',
                        'custom' => 'Custom URL',
                    ])
                    ->required()
                    ->default('none')
                    ->live(),

                Select::make('category_id')
                    ->label('Linked Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->visible(fn ($get) => $get('link_type') === 'category')
                    ->required(fn ($get) => $get('link_type') === 'category'),

                Select::make('product_id')
                    ->label('Linked Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->visible(fn ($get) => $get('link_type') === 'product')
                    ->required(fn ($get) => $get('link_type') === 'product'),

                TextInput::make('custom_url')
                    ->label('Custom URL Path')
                    ->nullable()
                    ->placeholder('e.g., /contact or https://...')
                    ->visible(fn ($get) => $get('link_type') === 'custom')
                    ->required(fn ($get) => $get('link_type') === 'custom'),
            ]);
    }
}
