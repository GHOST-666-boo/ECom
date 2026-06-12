<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];

    protected $fillable = [
        'name',
        'slug',
        'image',
        'parent_id',
        'is_active',
        'hsn_code',
        'gst_rate',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'gst_rate'  => 'decimal:2',
        ];
    }

    /**
     * Boot method to register model event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        $clearBentoCache = fn () => \Illuminate\Support\Facades\Cache::forget('homepage_bento_slots');
        static::saved($clearBentoCache);
        static::deleted($clearBentoCache);
    }

    /**
     * Get full R2 public URL for category image.
     */
    protected function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }
        return \Illuminate\Support\Facades\Storage::disk('r2')->url($this->image);
    }

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the products for the category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
