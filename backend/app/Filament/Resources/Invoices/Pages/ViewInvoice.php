<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Details')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('invoice_number')
                            ->label('Invoice #')
                            ->content(fn (Invoice $record) => $record->invoice_number),

                        Placeholder::make('invoice_type')
                            ->label('Type')
                            ->content(fn (Invoice $record) => $record->invoice_type),

                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn (Invoice $record) => ucfirst($record->status)),

                        Placeholder::make('invoice_date')
                            ->label('Date')
                            ->content(fn (Invoice $record) => $record->invoice_date ? $record->invoice_date->format('d M Y') : '—'),

                        Placeholder::make('order_ref')
                            ->label('Order Ref')
                            ->content(fn (Invoice $record) => $record->order?->order_number ?? '—'),

                        Placeholder::make('payment_method')
                            ->label('Payment Method')
                            ->content(fn (Invoice $record) => match ($record->order?->payment_method) {
                                'cod' => 'Cash on Delivery',
                                'razorpay' => 'Online (Razorpay)',
                                default => '—',
                            }),
                    ]),

                Section::make('Seller & Buyer')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('seller_name')
                            ->label('Seller')
                            ->content(fn (Invoice $record) => $record->seller_name),

                        Placeholder::make('seller_gstin')
                            ->label('Seller GSTIN')
                            ->content(fn (Invoice $record) => $record->seller_gstin),

                        Placeholder::make('buyer_name')
                            ->label('Buyer')
                            ->content(fn (Invoice $record) => $record->buyer_name),

                        Placeholder::make('buyer_gstin')
                            ->label('Buyer GSTIN')
                            ->content(fn (Invoice $record) => $record->buyer_gstin ?? 'N/A (B2C)'),

                        Placeholder::make('buyer_state')
                            ->label('Buyer State')
                            ->content(fn (Invoice $record) => $record->buyer_state),

                        Placeholder::make('buyer_address')
                            ->label('Buyer Address')
                            ->columnSpanFull()
                            ->content(fn (Invoice $record) => $record->buyer_address),
                    ]),

                Section::make('GST Financials')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('subtotal')
                            ->label('Taxable Value')
                            ->content(fn (Invoice $record) => '₹' . number_format($record->subtotal, 2)),

                        Placeholder::make('cgst')
                            ->label('CGST')
                            ->content(fn (Invoice $record) => '₹' . number_format($record->cgst, 2)),

                        Placeholder::make('sgst')
                            ->label('SGST')
                            ->content(fn (Invoice $record) => '₹' . number_format($record->sgst, 2)),

                        Placeholder::make('igst')
                            ->label('IGST')
                            ->content(fn (Invoice $record) => '₹' . number_format($record->igst, 2)),

                        Placeholder::make('shipping_amount')
                            ->label('Shipping')
                            ->content(fn (Invoice $record) => '₹' . number_format($record->shipping_amount, 2)),

                        Placeholder::make('total_amount')
                            ->label('Grand Total')
                            ->content(fn (Invoice $record) => '₹' . number_format($record->total_amount, 2)),
                    ]),

                Section::make('Invoice Items')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('items_table')
                            ->label('')
                            ->content(function (Invoice $record) {
                                $record->loadMissing('items');
                                $rows = '';
                                foreach ($record->items as $item) {
                                    $name = e($item->product_name);
                                    $hsn = e($item->hsn_code);
                                    $rate = $item->gst_rate . '%';
                                    $qty = $item->quantity;
                                    $price = '₹' . number_format($item->unit_price, 2);
                                    $taxable = '₹' . number_format($item->taxable_value, 2);
                                    $cgst = '₹' . number_format($item->cgst_amount, 2);
                                    $sgst = '₹' . number_format($item->sgst_amount, 2);
                                    $igst = '₹' . number_format($item->igst_amount, 2);
                                    $total = '₹' . number_format($item->line_total, 2);

                                    $rows .= "
                                        <tr style=\"border-bottom:1px solid rgba(255,255,255,0.07);\">
                                            <td style=\"padding:10px 8px;\">{$name}</td>
                                            <td style=\"padding:10px 8px;text-align:center;\">{$hsn}</td>
                                            <td style=\"padding:10px 8px;text-align:center;\">{$rate}</td>
                                            <td style=\"padding:10px 8px;text-align:center;\">{$qty}</td>
                                            <td style=\"padding:10px 8px;\">{$price}</td>
                                            <td style=\"padding:10px 8px;\">{$taxable}</td>
                                            <td style=\"padding:10px 8px;\">{$cgst}</td>
                                            <td style=\"padding:10px 8px;\">{$sgst}</td>
                                            <td style=\"padding:10px 8px;\">{$igst}</td>
                                            <td style=\"padding:10px 8px;font-weight:600;text-align:right;\">{$total}</td>
                                        </tr>
                                    ";
                                }

                                return new HtmlString("
                                    <table style=\"width:100%;border-collapse:collapse;font-size:0.88rem;\">
                                        <thead>
                                            <tr style=\"border-bottom:2px solid rgba(255,255,255,0.1);\">
                                                <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">Product</th>
                                                <th style=\"padding:8px;text-align:center;color:#9ca3af;font-weight:600;\">HSN</th>
                                                <th style=\"padding:8px;text-align:center;color:#9ca3af;font-weight:600;\">GST Rate</th>
                                                <th style=\"padding:8px;text-align:center;color:#9ca3af;font-weight:600;\">Qty</th>
                                                <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">Unit Price</th>
                                                <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">Taxable</th>
                                                <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">CGST</th>
                                                <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">SGST</th>
                                                <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">IGST</th>
                                                <th style=\"padding:8px;text-align:right;color:#9ca3af;font-weight:600;\">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>{$rows}</tbody>
                                    </table>
                                ");
                            }),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(function () {
                    try {
                        return app(InvoiceService::class)->generateDownloadUrl($this->record);
                    } catch (\Throwable $e) {
                        return null;
                    }
                })
                ->openUrlInNewTab(),
        ];
    }
}
