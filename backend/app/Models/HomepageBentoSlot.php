<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HomepageBentoSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_key',
        'title',
        'subtitle',
        'image',
        'icon',
        'badge',
        'theme',
        'link_type',
        'category_id',
        'product_id',
        'custom_url',
    ];

    protected $appends = ['image_url', 'computed_link'];

    /**
     * Boot method to register cache clear logic on model save.
     */
    protected static function boot()
    {
        parent::boot();

        $clearCache = fn () => \Illuminate\Support\Facades\Cache::forget('homepage_bento_slots');

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    /**
     * Get full public URL for the slot image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return Storage::disk('r2')->url($this->image);
    }

    /**
     * Compute front-end link path dynamically based on link configuration.
     */
    public function getComputedLinkAttribute(): ?string
    {
        switch ($this->link_type) {
            case 'category':
                return $this->category_id ? "/products?category_id={$this->category_id}" : null;
            
            case 'product':
                $product = $this->product;
                return $product ? "/products/{$product->slug}" : null;
            
            case 'custom':
                return $this->custom_url;
            
            case 'none':
            default:
                return null;
        }
    }

    /**
     * Get the category linked to this bento slot.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the product linked to this bento slot.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
