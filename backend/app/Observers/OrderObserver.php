<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;
use App\Mail\InvoiceGenerated;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    /**
     * Handle the Order "updated" event.
     *
     * Triggers invoice generation when order status transitions to 'delivered'.
     * The invoice is a legal document — only generated after delivery confirmation.
     */
    public function updated(Order $order): void
    {
        // Only trigger on status change TO 'delivered'
        if (!$order->wasChanged('status') || $order->status !== 'delivered') {
            return;
        }

        // Skip if invoice already exists (idempotency)
        if ($order->invoice()->exists()) {
            Log::info('Invoice already exists for delivered order — skipping auto-generation', [
                'order_id' => $order->id,
            ]);
            return;
        }

        try {
            $invoice = $this->invoiceService->generateInvoice($order);

            Log::info('Invoice auto-generated on order delivery', [
                'order_id'       => $order->id,
                'order_number'   => $order->order_number,
                'invoice_number' => $invoice->invoice_number,
            ]);

            // Queue invoice email to buyer (non-blocking)
            $order->loadMissing('user');
            if ($order->user?->email) {
                Mail::to($order->user->email)
                    ->queue(new InvoiceGenerated($invoice));
            }
        } catch (\Throwable $e) {
            // IMPORTANT: Do NOT re-throw. Order delivery must not roll back
            // because invoice generation failed. Log for manual retry.
            Log::error('Auto invoice generation failed on order delivery', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);
        }
    }
}
