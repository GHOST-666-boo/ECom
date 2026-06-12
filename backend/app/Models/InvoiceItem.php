<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'product_name',
        'hsn_code',
        'gst_rate',
        'quantity',
        'unit_price',
        'taxable_value',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'gst_rate'      => 'decimal:2',
            'quantity'      => 'integer',
            'unit_price'    => 'decimal:2',
            'taxable_value' => 'decimal:2',
            'cgst_amount'   => 'decimal:2',
            'sgst_amount'   => 'decimal:2',
            'igst_amount'   => 'decimal:2',
            'line_total'    => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
