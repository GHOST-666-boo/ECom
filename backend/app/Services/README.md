# Services

This directory contains service classes that encapsulate business logic and external integrations.

## ImageStorageService

The `ImageStorageService` handles all image upload operations to Cloudflare R2 storage.

### Features

- **EXIF Metadata Stripping**: Automatically removes EXIF metadata from uploaded images for privacy and security
- **Unique Filenames**: Generates unique filenames to prevent collisions
- **Public URL Generation**: Provides public URLs for accessing stored images
- **Batch Operations**: Supports uploading and deleting multiple images at once

### Usage

```php
use App\Services\ImageStorageService;

$imageService = new ImageStorageService();

// Upload a single image
$path = $imageService->uploadImage($uploadedFile, 'products');

// Upload multiple images
$paths = $imageService->uploadMultipleImages($files, 'products');

// Get public URL
$url = $imageService->getPublicUrl($path);

// Delete an image
$imageService->deleteImage($path);

// Delete multiple images
$imageService->deleteMultipleImages($paths);
```

### Configuration

The service uses the `r2` disk configured in `config/filesystems.php`. Ensure the following environment variables are set:

- `AWS_ACCESS_KEY_ID`: Your Cloudflare R2 access key ID
- `AWS_SECRET_ACCESS_KEY`: Your Cloudflare R2 secret access key
- `AWS_BUCKET`: Your R2 bucket name
- `AWS_ENDPOINT`: Your R2 endpoint URL (e.g., `https://account_id.r2.cloudflarestorage.com`)
- `AWS_USE_PATH_STYLE_ENDPOINT`: Set to `true` for R2 compatibility
- `AWS_URL`: Your custom domain URL for public access (optional)

### Requirements

This service requires the following packages:
- `aws/aws-sdk-php`: For S3-compatible storage operations
- `intervention/image-laravel`: For image processing and EXIF stripping
