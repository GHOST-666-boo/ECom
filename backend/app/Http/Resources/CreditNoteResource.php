<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'credit_note_number'  => $this->credit_note_number,
            'invoice_id'          => $this->invoice_id,
            'invoice_number'      => $this->invoice?->invoice_number,
            'order_id'            => $this->order_id,
            'order_number'        => $this->order?->order_number,
            'reason'              => $this->reason,
            'subtotal'            => $this->subtotal,
            'cgst'                => $this->cgst,
            'sgst'                => $this->sgst,
            'igst'                => $this->igst,
            'total_amount'        => $this->total_amount,
            'status'              => $this->status,
            'created_at'          => $this->created_at,
        ];
    }
}
