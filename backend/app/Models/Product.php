<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['image_urls'];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'images',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot method to add model event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Validate stock is non-negative before saving (Requirement 15.8)
        static::saving(function ($product) {
            if ($product->stock < 0) {
                throw new \InvalidArgumentException('Stock quantity cannot be negative');
            }
        });
    }

    /**
     * Get full R2 public URLs for product images.
     */
    protected function getImageUrlsAttribute(): array
    {
        if (!$this->images) {
            return [];
        }

        $urls = [];
        foreach ($this->images as $item) {
            // Handle nested array format from old Filament uploads
            if (is_array($item)) {
                $path = $item['path'] ?? $item['url'] ?? null;
                if (!$path || !is_string($path)) {
                    continue;
                }
            } else {
                $path = $item;
            }

            if (!is_string($path)) {
                continue;
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                $urls[] = $path;
            } else {
                $urls[] = \Illuminate\Support\Facades\Storage::disk('r2')->url($path);
            }
        }

        return $urls;
    }

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the cart items for the product.
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
