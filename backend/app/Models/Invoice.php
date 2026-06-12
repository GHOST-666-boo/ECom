<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'buyer_name',
        'buyer_address',
        'buyer_state',
        'buyer_gstin',
        'invoice_type',
        'seller_gstin',
        'seller_name',
        'seller_address',
        'seller_state',
        'invoice_date',
        'subtotal',
        'shipping_amount',
        'shipping_gst_rate',
        'shipping_cgst',
        'shipping_sgst',
        'shipping_igst',
        'cgst',
        'sgst',
        'igst',
        'total_amount',
        'pdf_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'      => 'date',
            'subtotal'          => 'decimal:2',
            'shipping_amount'   => 'decimal:2',
            'shipping_gst_rate' => 'decimal:2',
            'shipping_cgst'     => 'decimal:2',
            'shipping_sgst'     => 'decimal:2',
            'shipping_igst'     => 'decimal:2',
            'cgst'              => 'decimal:2',
            'sgst'              => 'decimal:2',
            'igst'              => 'decimal:2',
            'total_amount'      => 'decimal:2',
        ];
    }

    /**
     * The order this invoice belongs to.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * All line items on this invoice.
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Credit note issued against this invoice (if cancelled).
     */
    public function creditNote()
    {
        return $this->hasOne(CreditNote::class);
    }
}
