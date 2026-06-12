<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_method',
        'payment_id',
        'payment_status',
        'tracking_number',
        'courier_name',
        'total',
        'address_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'address_snapshot' => 'array',
        ];
    }

    /**
     * Check if order is shipped or delivered (has tracking info).
     */
    public function isShipped(): bool
    {
        return in_array($this->status, ['shipped', 'delivered']);
    }

    /**
     * Check if COD payment is collected.
     */
    public function isCodPaid(): bool
    {
        return $this->payment_method === 'cod' && $this->payment_status === 'paid';
    }

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the invoice for this order.
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
