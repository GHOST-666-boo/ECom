<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(InvoiceService::class);
        
        $data['invoice_number'] = $service->getNextInvoiceNumber();
        $data['seller_name'] = config('invoice.seller_name');
        $data['seller_address'] = config('invoice.seller_address');
        $data['seller_state'] = config('invoice.seller_state');
        $data['seller_gstin'] = config('invoice.seller_gstin');
        $data['status'] = 'generated';

        return $data;
    }

    protected function afterCreate(): void
    {
        $invoice = $this->record;
        
        try {
            $pdfPath = app(InvoiceService::class)->generatePDF($invoice->load('items'));
            $invoice->update(['pdf_path' => $pdfPath]);
        } catch (\Throwable $e) {
            Log::warning('Manual Invoice PDF generation failed after creation', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
