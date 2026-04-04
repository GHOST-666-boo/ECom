<?php

namespace Tests\Unit;

use App\Services\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageStorageServiceTest extends TestCase
{
    protected ImageStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageStorageService();
        
        // Fake the R2 storage disk
        Storage::fake('r2');
    }

    public function test_upload_image_stores_file_and_returns_path(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $path = $this->service->uploadImage($file, 'products');
        
        // Assert the path is in the correct format
        $this->assertStringStartsWith('products/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        
        // Assert the file was stored
        Storage::disk('r2')->assertExists($path);
    }

    public function test_upload_multiple_images_stores_all_files(): void
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->image('test2.png'),
            UploadedFile::fake()->image('test3.webp'),
        ];
        
        $paths = $this->service->uploadMultipleImages($files, 'products');
        
        $this->assertCount(3, $paths);
        
        foreach ($paths as $path) {
            Storage::disk('r2')->assertExists($path);
        }
    }

    public function test_get_public_url_returns_valid_url(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $path = $this->service->uploadImage($file, 'products');
        
        $url = $this->service->getPublicUrl($path);
        
        $this->assertIsString($url);
        $this->assertNotEmpty($url);
    }

    public function test_delete_image_removes_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $path = $this->service->uploadImage($file, 'products');
        
        Storage::disk('r2')->assertExists($path);
        
        $result = $this->service->deleteImage($path);
        
        $this->assertTrue($result);
        Storage::disk('r2')->assertMissing($path);
    }

    public function test_delete_multiple_images_removes_all_files(): void
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->image('test2.jpg'),
        ];
        
        $paths = $this->service->uploadMultipleImages($files, 'products');
        
        foreach ($paths as $path) {
            Storage::disk('r2')->assertExists($path);
        }
        
        $result = $this->service->deleteMultipleImages($paths);
        
        $this->assertTrue($result);
        
        foreach ($paths as $path) {
            Storage::disk('r2')->assertMissing($path);
        }
    }
}
