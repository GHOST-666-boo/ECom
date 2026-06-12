<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_note_number',
        'invoice_id',
        'order_id',
        'reason',
        'subtotal',
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
            'subtotal'     => 'decimal:2',
            'cgst'         => 'decimal:2',
            'sgst'         => 'decimal:2',
            'igst'         => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
