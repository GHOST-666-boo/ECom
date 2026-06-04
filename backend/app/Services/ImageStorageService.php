<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageStorageService
{
    /**
     * Upload an image to Cloudflare R2, strip EXIF metadata, and return the storage path.
     *
     * @param UploadedFile $file The uploaded image file
     * @param string $directory The directory path within the R2 bucket (e.g., 'products', 'categories')
     * @return string The storage path of the uploaded image
     */
    public function uploadImage($file, string $directory = 'images'): string
    {
        // Read image content - works for both local files and remote storage (R2/S3)
        // getRealPath() fails for remote disks, so we read binary content directly
        $content = method_exists($file, 'get') ? $file->get() : file_get_contents($file->getRealPath());
        $image = Image::read($content);

        // Encode the image without EXIF metadata
        $encodedImage = $image->encode();

        // Derive extension from the encoded format (don't trust client-provided extension)
        $extension = $encodedImage->extension ?? 'jpg';
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $path = $directory . '/' . $filename;

        // Upload to configured storage disk
        Storage::disk('r2')->put($path, (string) $encodedImage);

        return $path;
    }

    /**
     * Upload multiple images to storage.
     *
     * @param array $files Array of UploadedFile instances
     * @param string $directory The directory path within the storage disk
     * @return array Array of storage paths
     */
    public function uploadMultipleImages(array $files, string $directory = 'images'): array
    {
        $paths = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->uploadImage($file, $directory);
            }
        }
        
        return $paths;
    }

    /**
     * Generate a public URL for an image.
     *
     * @param string $path The storage path of the image
     * @return string The public URL
     */
    public function getPublicUrl(string $path): string
    {
        return Storage::disk('r2')->url($path);
    }

    /**
     * Generate public URLs for multiple images.
     *
     * @param array $paths Array of storage paths
     * @return array Array of public URLs
     */
    public function getPublicUrls(array $paths): array
    {
        return array_map(fn($path) => $this->getPublicUrl($path), $paths);
    }

    /**
     * Delete an image from storage.
     *
     * @param string $path The storage path of the image
     * @return bool True if deleted successfully
     */
    public function deleteImage(string $path): bool
    {
        return Storage::disk('r2')->delete($path);
    }

    /**
     * Delete multiple images from storage.
     *
     * @param array $paths Array of storage paths
     * @return bool True if all deleted successfully
     */
    public function deleteMultipleImages(array $paths): bool
    {
        return Storage::disk('r2')->delete($paths);
    }
}
