<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Rules\ValidHsnCode;
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
                    ->helperText('Leave empty for top-level category')
                    ->columnSpanFull(),

                // ── GST Fields ─────────────────────────────────────────────
                TextInput::make('hsn_code')
                    ->label('HSN Code')
                    ->maxLength(8)
                    ->nullable()
                    ->rule(new ValidHsnCode())
                    ->helperText('4–8 digit HSN code. Default for all products in this category (e.g. 6117 for knitted accessories).'),

                TextInput::make('gst_rate')
                    ->label('GST Rate (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->nullable()
                    ->helperText('Default GST rate for products in this category (e.g. 12.00 for 12%). Leave blank if no GST applies.'),
                
                FileUpload::make('image')
                    ->label('Category Image')
                    ->image()
                    ->disk('r2')
                    ->directory('categories')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->nullable()
                    ->helperText('Upload category image (max 2MB, JPEG/PNG/WebP). EXIF metadata will be stripped automatically.')
                    ->saveUploadedFileUsing(function ($file) {
                        $imageService = app(ImageStorageService::class);
                        return $imageService->uploadImage($file, 'categories');
                    })
                    ->columnSpanFull(),
                
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active categories are visible to customers')
                    ->columnSpanFull(),
            ]);
    }
}
