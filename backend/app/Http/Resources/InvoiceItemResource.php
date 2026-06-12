<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'product_name'  => $this->product_name,
            'hsn_code'      => $this->hsn_code,
            'gst_rate'      => $this->gst_rate,
            'quantity'      => $this->quantity,
            'unit_price'    => $this->unit_price,
            'taxable_value' => $this->taxable_value,
            'cgst_amount'   => $this->cgst_amount,
            'sgst_amount'   => $this->sgst_amount,
            'igst_amount'   => $this->igst_amount,
            'line_total'    => $this->line_total,
        ];
    }
}
