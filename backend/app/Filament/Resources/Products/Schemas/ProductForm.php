<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Services\ImageStorageService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Select the product category'),
                
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, ?Product $record) {
                        $slug = Str::slug($state);
                        
                        // Handle duplicate slugs by appending numeric suffix
                        $originalSlug = $slug;
                        $counter = 2;
                        
                        while (Product::where('slug', $slug)
                            ->when($record, fn($query) => $query->where('id', '!=', $record->id))
                            ->exists()) {
                            $slug = $originalSlug . '-' . $counter;
                            $counter++;
                        }
                        
                        $set('slug', $slug);
                    }),
                
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Product::class, 'slug', ignoreRecord: true)
                    ->helperText('Auto-generated from name with duplicate handling'),
                
                RichEditor::make('description')
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull()
                    ->helperText('Product description (max 5000 characters)'),
                
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('₹')
                    ->helperText('Product price in INR'),
                
                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->integer()
                    ->helperText('Available stock quantity'),
                
                FileUpload::make('images')
                    ->label('Product Images')
                    ->image()
                    ->multiple()
                    ->disk(config('filesystems.default'))
                    ->directory('products')
                    ->storeFiles(false)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->maxFiles(5)
                    ->reorderable()
                    ->columnSpanFull()
                    ->helperText('Upload up to 5 images (max 2MB each, JPEG/PNG/WebP). EXIF metadata will be stripped automatically.')
                    ->saveUploadedFileUsing(function ($file) {
                        $imageService = app(ImageStorageService::class);
                        return $imageService->uploadImage($file, 'products');
                    }),
                
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active products are visible to customers'),
            ]);
    }
}
