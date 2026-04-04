<?php

namespace App\Services;

/**
 * Example usage of ImageStorageService
 * 
 * This file demonstrates how to use the ImageStorageService in various contexts.
 * Delete this file after reviewing the examples.
 */

// Example 1: Upload a single product image in a controller
class ProductController
{
    public function store(Request $request, ImageStorageService $imageService)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,webp|max:2048', // 2MB max
        ]);

        // Upload the image
        $imagePath = $imageService->uploadImage($request->file('image'), 'products');

        // Create the product with the image path
        $product = Product::create([
            'name' => $validated['name'],
            'image' => $imagePath,
        ]);

        // Get the public URL for the response
        $product->image_url = $imageService->getPublicUrl($imagePath);

        return response()->json($product);
    }
}

// Example 2: Upload multiple product images
class ProductWithMultipleImagesController
{
    public function store(Request $request, ImageStorageService $imageService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Upload all images
        $imagePaths = $imageService->uploadMultipleImages($request->file('images'), 'products');

        // Create the product with image paths stored as JSON
        $product = Product::create([
            'name' => $validated['name'],
            'images' => $imagePaths, // Laravel will automatically cast this to JSON
        ]);

        // Get public URLs for the response
        $product->image_urls = $imageService->getPublicUrls($imagePaths);

        return response()->json($product);
    }
}

// Example 3: Update product images (delete old, upload new)
class ProductUpdateController
{
    public function update(Request $request, Product $product, ImageStorageService $imageService)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'images' => 'sometimes|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,webp|max:2048',
        ]);

        // If new images are provided, delete old ones and upload new ones
        if ($request->hasFile('images')) {
            // Delete old images from R2
            if (!empty($product->images)) {
                $imageService->deleteMultipleImages($product->images);
            }

            // Upload new images
            $imagePaths = $imageService->uploadMultipleImages($request->file('images'), 'products');
            $product->images = $imagePaths;
        }

        // Update other fields
        if (isset($validated['name'])) {
            $product->name = $validated['name'];
        }

        $product->save();

        // Get public URLs for the response
        $product->image_urls = $imageService->getPublicUrls($product->images);

        return response()->json($product);
    }
}

// Example 4: Delete product and its images
class ProductDeleteController
{
    public function destroy(Product $product, ImageStorageService $imageService)
    {
        // Delete images from R2 before deleting the product
        if (!empty($product->images)) {
            $imageService->deleteMultipleImages($product->images);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}

// Example 5: Using the service in a Filament resource
class ProductResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            
            FileUpload::make('images')
                ->image()
                ->multiple()
                ->maxFiles(5)
                ->maxSize(2048) // 2MB
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('r2') // Use R2 disk directly
                ->directory('products')
                ->visibility('public')
                ->imageEditor()
                ->saveUploadedFileUsing(function ($file, $component) {
                    $imageService = app(ImageStorageService::class);
                    return $imageService->uploadImage($file, 'products');
                }),
        ]);
    }
}
