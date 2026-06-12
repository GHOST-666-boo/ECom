<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Product;
use App\Rules\ValidHsnCode;
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
                    ->live()  // Enables reactivity — triggers afterStateUpdated on change
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Auto-fill HSN + GST from selected category (if not already set)
                        if ($state) {
                            $category = Category::find($state);
                            if ($category) {
                                $set('hsn_code', $category->hsn_code);
                                $set('gst_rate', $category->gst_rate);
                            }
                        }
                    })
                    ->helperText('Selecting a category will auto-fill HSN Code and GST Rate below.')
                    ->columnSpanFull(),
                
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
                
                // ── GST Fields ─────────────────────────────────────────────────────
                // Auto-filled from category on selection. Admin can override or clear.
                // If left blank, the value from the category is used at invoice time.

                TextInput::make('hsn_code')
                    ->label('HSN Code (Product Override)')
                    ->maxLength(8)
                    ->nullable()
                    ->rule(new ValidHsnCode())
                    ->helperText(function (callable $get) {
                        $categoryId = $get('category_id');
                        if ($categoryId) {
                            $category = Category::find($categoryId);
                            if ($category?->hsn_code) {
                                return "Leave blank to use category default (HSN: {$category->hsn_code})";
                            }
                        }
                        return 'Leave blank to use category default HSN code. Set here to override for this product only.';
                    }),

                TextInput::make('gst_rate')
                    ->label('GST Rate % (Product Override)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->nullable()
                    ->helperText(function (callable $get) {
                        $categoryId = $get('category_id');
                        if ($categoryId) {
                            $category = Category::find($categoryId);
                            if ($category?->gst_rate !== null) {
                                return "Leave blank to use category default (GST: {$category->gst_rate}%)";
                            }
                        }
                        return 'Leave blank to use category default GST rate. Set here to override for this product only.';
                    }),

                FileUpload::make('images')
                    ->label('Product Images')
                    ->image()
                    ->multiple()
                    ->disk('r2')
                    ->directory('products')
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
                    ->helperText('Only active products are visible to customers')
                    ->columnSpanFull(),
            ]);
    }
}
