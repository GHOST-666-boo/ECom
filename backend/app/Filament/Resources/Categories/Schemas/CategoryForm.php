<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Services\ImageStorageService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Category::class, 'slug', ignoreRecord: true)
                    ->helperText('Auto-generated from name, but can be customized'),
                
                Select::make('parent_id')
                    ->label('Parent Category')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Leave empty for top-level category'),
                
                FileUpload::make('image')
                    ->label('Category Image')
                    ->image()
                    ->disk(config('filesystems.default'))
                    ->directory('categories')
                    ->storeFiles(false)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->nullable()
                    ->helperText('Upload category image (max 2MB, JPEG/PNG/WebP). EXIF metadata will be stripped automatically.')
                    ->saveUploadedFileUsing(function ($file) {
                        $imageService = app(ImageStorageService::class);
                        return $imageService->uploadImage($file, 'categories');
                    }),
                
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active categories are visible to customers'),
            ]);
    }
}
