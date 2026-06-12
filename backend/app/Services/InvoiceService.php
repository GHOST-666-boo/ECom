<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /**
     * Generate a GST-compliant invoice for the given order.
     * Idempotent: returns existing invoice if one already exists for this order.
     *
     * Triggered when order.status === 'delivered'.
     *
     * @throws \Exception
     */
    public function generateInvoice(Order $order): Invoice
    {
        // --- Idempotency guard: return existing invoice silently ---
        if ($order->invoice) {
            return $order->invoice->load('items');
        }

        // Eager-load all needed relations
        $order->loadMissing(['orderItems.product.category', 'user']);

        // Resolve buyer details from address snapshot
        $snapshot    = $order->address_snapshot ?? [];
        $buyerName   = $snapshot['name']    ?? ($order->user->name ?? 'Customer');
        $buyerState  = $snapshot['state']   ?? '';
        $buyerAddr   = $this->formatAddress($snapshot);

        // Seller details (from config — snapshot at invoice time)
        $sellerState = config('invoice.seller_state', 'Delhi');

        // Determine intra vs inter state
        $isIntraState = (strtolower(trim($buyerState)) === strtolower(trim($sellerState)));

        // Build item data with resolved HSN + GST
        $itemsData = [];
        foreach ($order->orderItems as $orderItem) {
            $product  = $orderItem->product;
            $category = $product?->category;

            // Priority: product.hsn_code ?? category.hsn_code
            $hsnCode = $product?->hsn_code ?? $category?->hsn_code ?? '0000';
            // Priority: product.gst_rate ?? category.gst_rate
            $gstRate = $product?->gst_rate ?? $category?->gst_rate ?? 0;

            $lineTotal    = round($orderItem->quantity * $orderItem->price, 2);
            $taxableValue = round($lineTotal / (1 + ($gstRate / 100)), 2);
            $gstAmount    = round($lineTotal - $taxableValue, 2);

            if ($isIntraState) {
                $cgst = round($gstAmount / 2, 2);
                $sgst = round($gstAmount / 2, 2);
                $igst = 0;
                // Re-adjust taxable value to match exact line total minus CGST and SGST
                $taxableValue = round($lineTotal - ($cgst + $sgst), 2);
            } else {
                $cgst = 0;
                $sgst = 0;
                $igst = $gstAmount;
                // Re-adjust taxable value to match exact line total minus IGST
                $taxableValue = round($lineTotal - $igst, 2);
            }

            $itemsData[] = [
                'product_id'    => $orderItem->product_id,
                'product_name'  => $product?->name ?? 'Product',
                'hsn_code'      => $hsnCode,
                'gst_rate'      => $gstRate,
                'quantity'      => $orderItem->quantity,
                'unit_price'    => $orderItem->price,
                'taxable_value' => $taxableValue,
                'cgst_amount'   => $cgst,
                'sgst_amount'   => $sgst,
                'igst_amount'   => $igst,
                'line_total'    => $lineTotal,
            ];
        }

        // Calculate invoice totals
        $subtotal   = array_sum(array_column($itemsData, 'taxable_value'));
        $totalCgst  = array_sum(array_column($itemsData, 'cgst_amount'));
        $totalSgst  = array_sum(array_column($itemsData, 'sgst_amount'));
        $totalIgst  = array_sum(array_column($itemsData, 'igst_amount'));

        // Shipping GST calculation
        $shippingAmount  = 0; // No shipping charge in current order model; extend when needed
        $shippingGstRate = config('invoice.shipping_gst', 18.00);
        $shippingCgst    = 0;
        $shippingSgst    = 0;
        $shippingIgst    = 0;

        if ($shippingAmount > 0) {
            $shippingGst = round($shippingAmount * ($shippingGstRate / 100), 2);
            if ($isIntraState) {
                $shippingCgst = round($shippingGst / 2, 2);
                $shippingSgst = round($shippingGst / 2, 2);
            } else {
                $shippingIgst = $shippingGst;
            }
        }

        $totalAmount = round(
            $subtotal + $totalCgst + $totalSgst + $totalIgst
            + $shippingAmount + $shippingCgst + $shippingSgst + $shippingIgst,
            2
        );

        // Wrap everything in a DB transaction
        $invoice = DB::transaction(function () use (
            $order, $buyerName, $buyerAddr, $buyerState, $sellerState,
            $isIntraState, $itemsData,
            $subtotal, $totalCgst, $totalSgst, $totalIgst,
            $shippingAmount, $shippingGstRate, $shippingCgst, $shippingSgst, $shippingIgst,
            $totalAmount
        ) {
            $invoiceNumber = $this->getNextInvoiceNumber();

            $invoice = Invoice::create([
                'invoice_number'    => $invoiceNumber,
                'order_id'          => $order->id,
                'buyer_name'        => $buyerName,
                'buyer_address'     => $buyerAddr,
                'buyer_state'       => $buyerState,
                'buyer_gstin'       => null, // Updated via API if B2B
                'invoice_type'      => 'B2C',
                'seller_gstin'      => config('invoice.seller_gstin'),
                'seller_name'       => config('invoice.seller_name'),
                'seller_address'    => config('invoice.seller_address'),
                'seller_state'      => config('invoice.seller_state'),
                'invoice_date'      => now()->toDateString(),
                'subtotal'          => $subtotal,
                'shipping_amount'   => $shippingAmount,
                'shipping_gst_rate' => $shippingGstRate,
                'shipping_cgst'     => $shippingCgst,
                'shipping_sgst'     => $shippingSgst,
                'shipping_igst'     => $shippingIgst,
                'cgst'              => $totalCgst,
                'sgst'              => $totalSgst,
                'igst'              => $totalIgst,
                'total_amount'      => $totalAmount,
                'status'            => 'generated',
            ]);

            foreach ($itemsData as $item) {
                InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
            }

            return $invoice;
        });

        // Generate PDF and store on R2 (outside transaction — non-critical)
        try {
            $pdfPath = $this->generatePDF($invoice->load('items'));
            $invoice->update(['pdf_path' => $pdfPath]);
        } catch (\Throwable $e) {
            Log::warning('Invoice PDF generation failed — invoice created without PDF', [
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'error'          => $e->getMessage(),
            ]);
        }

        Log::info('Invoice generated successfully', [
            'invoice_number' => $invoice->invoice_number,
            'order_id'       => $order->id,
            'total'          => $invoice->total_amount,
        ]);

        return $invoice->load('items');
    }

    /**
     * Generate the next sequential invoice number using financial year.
     * Format: VRD-INV-2025-26-00001
     * Uses DB lock to prevent duplicate numbers under concurrent requests.
     */
    public function getNextInvoiceNumber(): string
    {
        $fy = $this->getCurrentFinancialYear(); // e.g., "2025-26"

        // Lock the last invoice row for this financial year to prevent race conditions
        $lastInvoice = Invoice::where('invoice_number', 'like', "VRD-INV-{$fy}-%")
            ->lockForUpdate()
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract last 5 digits and increment
            $lastSeq = (int) substr($lastInvoice->invoice_number, -5);
            $newSeq  = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }

        return 'VRD-INV-' . $fy . '-' . str_pad($newSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate the next credit note number using financial year.
     * Format: VRD-CN-2025-26-00001
     */
    public function getNextCreditNoteNumber(): string
    {
        $fy = $this->getCurrentFinancialYear();

        $lastCn = CreditNote::where('credit_note_number', 'like', "VRD-CN-{$fy}-%")
            ->lockForUpdate()
            ->orderBy('credit_note_number', 'desc')
            ->first();

        if ($lastCn) {
            $lastSeq = (int) substr($lastCn->credit_note_number, -5);
            $newSeq  = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }

        return 'VRD-CN-' . $fy . '-' . str_pad($newSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Returns the current Indian financial year string.
     * April–March: April 2025 → March 2026 = "2025-26"
     */
    public function getCurrentFinancialYear(): string
    {
        $month = (int) date('n');
        $year  = (int) date('Y');

        if ($month >= 4) {
            return $year . '-' . substr($year + 1, -2);  // e.g., "2025-26"
        }

        return ($year - 1) . '-' . substr($year, -2);    // e.g., "2024-25"
    }

    /**
     * Calculate GST breakdown per item and return grouped rate-wise summary.
     * Used for PDF rate-wise summary table.
     */
    public function calculateGstSummary(Invoice $invoice): array
    {
        $groups = [];

        foreach ($invoice->items as $item) {
            $rate = (string) $item->gst_rate;

            if (!isset($groups[$rate])) {
                $groups[$rate] = [
                    'gst_rate'      => $item->gst_rate,
                    'taxable_value' => 0,
                    'cgst'          => 0,
                    'sgst'          => 0,
                    'igst'          => 0,
                ];
            }

            $groups[$rate]['taxable_value'] += $item->taxable_value;
            $groups[$rate]['cgst']          += $item->cgst_amount;
            $groups[$rate]['sgst']          += $item->sgst_amount;
            $groups[$rate]['igst']          += $item->igst_amount;
        }

        // Add shipping as a separate SAC group if applicable
        if ($invoice->shipping_amount > 0) {
            $rate = (string) $invoice->shipping_gst_rate;
            $label = 'Shipping (SAC ' . config('invoice.shipping_sac') . ')';

            $groups['shipping'] = [
                'gst_rate'      => $invoice->shipping_gst_rate,
                'label'         => $label,
                'taxable_value' => $invoice->shipping_amount,
                'cgst'          => $invoice->shipping_cgst,
                'sgst'          => $invoice->shipping_sgst,
                'igst'          => $invoice->shipping_igst,
            ];
        }

        ksort($groups);
        return array_values($groups);
    }

    /**
     * Generate PDF for the invoice, upload to R2, and return the storage path.
     */
    public function generatePDF(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'order']);
        $gstSummary = $this->calculateGstSummary($invoice);

        $pdf = Pdf::loadView('invoices.tax-invoice', [
            'invoice'    => $invoice,
            'gstSummary' => $gstSummary,
            'totalInWords' => $this->numberToWords((float) $invoice->total_amount),
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'defaultFont'          => 'sans-serif',
        ]);

        $pdfContent = $pdf->output();
        $path       = config('invoice.pdf_directory') . '/' . $invoice->invoice_number . '.pdf';

        Storage::disk(config('invoice.pdf_disk', 'r2'))->put($path, $pdfContent);

        return $path;
    }

    /**
     * Generate a temporary signed download URL for the invoice PDF.
     * Falls back to in-memory PDF generation if pdf_path is missing.
     */
    public function generateDownloadUrl(Invoice $invoice): string
    {
        $disk = config('invoice.pdf_disk', 'r2');

        // Re-generate PDF if missing
        if (!$invoice->pdf_path || !Storage::disk($disk)->exists($invoice->pdf_path)) {
            $invoice->loadMissing('items');
            $pdfPath = $this->generatePDF($invoice);
            $invoice->update(['pdf_path' => $pdfPath]);
        }

        // R2 signed URLs: use temporaryUrl if disk supports it, else use public URL
        try {
            return Storage::disk($disk)->temporaryUrl(
                $invoice->pdf_path,
                now()->addMinutes(config('invoice.signed_url_minutes', 15))
            );
        } catch (\RuntimeException $e) {
            // Fallback: public URL (for local/non-signed disks)
            return Storage::disk($disk)->url($invoice->pdf_path);
        }
    }

    /**
     * Generate a credit note for a cancelled invoice.
     * Also marks the original invoice as 'cancelled'.
     *
     * @throws \Exception
     */
    public function generateCreditNote(Invoice $invoice, string $reason = 'cancellation'): CreditNote
    {
        if ($invoice->creditNote) {
            return $invoice->creditNote;
        }

        $creditNote = DB::transaction(function () use ($invoice, $reason) {
            $cnNumber = $this->getNextCreditNoteNumber();

            $creditNote = CreditNote::create([
                'credit_note_number' => $cnNumber,
                'invoice_id'         => $invoice->id,
                'order_id'           => $invoice->order_id,
                'reason'             => $reason,
                'subtotal'           => $invoice->subtotal,
                'cgst'               => $invoice->cgst,
                'sgst'               => $invoice->sgst,
                'igst'               => $invoice->igst,
                'total_amount'       => $invoice->total_amount,
                'status'             => 'generated',
            ]);

            $invoice->update(['status' => 'cancelled']);

            return $creditNote;
        });

        Log::info('Credit note generated', [
            'credit_note_number' => $creditNote->credit_note_number,
            'invoice_id'         => $invoice->id,
        ]);

        return $creditNote;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function formatAddress(array $snapshot): string
    {
        return implode(', ', array_filter([
            $snapshot['line1']   ?? null,
            $snapshot['line2']   ?? null,
            $snapshot['city']    ?? null,
            $snapshot['state']   ?? null,
            $snapshot['pincode'] ?? null,
        ]));
    }

    /**
     * Convert a decimal amount to Indian English words.
     * Example: 1941.50 → "One Thousand Nine Hundred Forty One and Fifty Paise Only"
     */
    public function numberToWords(float $amount): string
    {
        $amount   = round($amount, 2);
        $rupees   = (int) $amount;
        $paise    = (int) round(($amount - $rupees) * 100);

        $words = $this->convertToWords($rupees) . ' Rupees';
        if ($paise > 0) {
            $words .= ' and ' . $this->convertToWords($paise) . ' Paise';
        }
        return $words . ' Only';
    }

    private function convertToWords(int $number): string
    {
        if ($number === 0) return 'Zero';

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $result = '';

        if ($number >= 10000000) {
            $result .= $this->convertToWords((int)($number / 10000000)) . ' Crore ';
            $number %= 10000000;
        }
        if ($number >= 100000) {
            $result .= $this->convertToWords((int)($number / 100000)) . ' Lakh ';
            $number %= 100000;
        }
        if ($number >= 1000) {
            $result .= $this->convertToWords((int)($number / 1000)) . ' Thousand ';
            $number %= 1000;
        }
        if ($number >= 100) {
            $result .= $ones[(int)($number / 100)] . ' Hundred ';
            $number %= 100;
        }
        if ($number >= 20) {
            $result .= $tens[(int)($number / 10)] . ' ';
            $number %= 10;
        }
        if ($number > 0) {
            $result .= $ones[$number] . ' ';
        }

        return trim($result);
    }
}
