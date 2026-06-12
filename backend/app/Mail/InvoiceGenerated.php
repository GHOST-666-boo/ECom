<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceGenerated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Vriddhi Invoice {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-generated',
            with: [
                'invoice'     => $this->invoice->load('items', 'order'),
                'orderNumber' => $this->invoice->order?->order_number,
            ],
        );
    }

    public function attachments(): array
    {
        // Attach PDF — fetch from R2 or generate in-memory as fallback
        try {
            $disk = config('invoice.pdf_disk', 'r2');

            if ($this->invoice->pdf_path && Storage::disk($disk)->exists($this->invoice->pdf_path)) {
                $pdfData = Storage::disk($disk)->get($this->invoice->pdf_path);
            } else {
                // Fallback: generate in-memory
                $service = app(InvoiceService::class);
                $this->invoice->loadMissing('items');
                $gstSummary = $service->calculateGstSummary($this->invoice);

                $pdfData = Pdf::loadView('invoices.tax-invoice', [
                    'invoice'      => $this->invoice,
                    'gstSummary'   => $gstSummary,
                    'totalInWords' => $service->numberToWords((float) $this->invoice->total_amount),
                ])->output();
            }

            return [
                Attachment::fromData(fn () => $pdfData, $this->invoice->invoice_number . '.pdf')
                    ->withMime('application/pdf'),
            ];
        } catch (\Throwable $e) {
            Log::warning('Could not attach invoice PDF to email', [
                'invoice_id' => $this->invoice->id,
                'error'      => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Mark invoice as 'sent' on successful email delivery.
     */
    public function handle(): void
    {
        $this->invoice->update(['status' => 'sent']);

        Log::info('Invoice email sent successfully', [
            'invoice_id'     => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
        ]);
    }

    /**
     * Handle email job failure — keep status as 'generated' for manual retry.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice email delivery failed', [
            'invoice_id'     => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'error'          => $exception->getMessage(),
        ]);
        // status intentionally NOT updated — stays 'generated' so retry is possible
    }
}
