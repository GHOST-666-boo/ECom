<?php

namespace App\Http\Resources;

use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Invoice $this */
        $service    = app(InvoiceService::class);
        $gstSummary = $service->calculateGstSummary($this->resource);

        // Generate temporary download URL only when pdf_path exists
        $pdfUrl = null;
        if ($this->pdf_path) {
            try {
                $pdfUrl = $service->generateDownloadUrl($this->resource);
            } catch (\Throwable $e) {
                $pdfUrl = null;
            }
        }

        return [
            'id'                 => $this->id,
            'invoice_number'     => $this->invoice_number,
            'invoice_type'       => $this->invoice_type,
            'invoice_date'       => $this->invoice_date?->toDateString(),
            'status'             => $this->status,

            // Order reference
            'order_id'           => $this->order_id,
            'order_number'       => $this->order?->order_number,

            // Seller snapshot
            'seller_name'        => $this->seller_name,
            'seller_gstin'       => $this->seller_gstin,
            'seller_state'       => $this->seller_state,
            'seller_address'     => $this->seller_address,

            // Buyer snapshot
            'buyer_name'         => $this->buyer_name,
            'buyer_address'      => $this->buyer_address,
            'buyer_state'        => $this->buyer_state,
            'buyer_gstin'        => $this->buyer_gstin,

            // Financials
            'subtotal'           => $this->subtotal,
            'shipping_amount'    => $this->shipping_amount,
            'shipping_gst_rate'  => $this->shipping_gst_rate,
            'shipping_cgst'      => $this->shipping_cgst,
            'shipping_sgst'      => $this->shipping_sgst,
            'shipping_igst'      => $this->shipping_igst,
            'cgst'               => $this->cgst,
            'sgst'               => $this->sgst,
            'igst'               => $this->igst,
            'total_amount'       => $this->total_amount,
            'total_in_words'     => $service->numberToWords((float) $this->total_amount),

            // Line items
            'items'              => InvoiceItemResource::collection($this->whenLoaded('items')),

            // GST rate-wise summary
            'gst_rate_summary'   => $gstSummary,

            // PDF download
            'pdf_url'            => $pdfUrl,

            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
