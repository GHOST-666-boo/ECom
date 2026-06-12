<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('invoice_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'B2C',
                        'info'    => 'B2B',
                    ]),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('buyer_name')
                    ->label('Buyer')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('buyer_state')
                    ->label('State')
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cgst')
                    ->label('CGST')
                    ->money('INR'),

                Tables\Columns\TextColumn::make('sgst')
                    ->label('SGST')
                    ->money('INR'),

                Tables\Columns\TextColumn::make('igst')
                    ->label('IGST')
                    ->money('INR'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning'  => 'generated',
                        'success'  => 'sent',
                        'danger'   => 'cancelled',
                    ]),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'generated' => 'Generated',
                        'sent'      => 'Sent',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('invoice_type')
                    ->label('Type')
                    ->options([
                        'B2C' => 'B2C (Consumer)',
                        'B2B' => 'B2B (Business)',
                    ]),

                Tables\Filters\Filter::make('invoice_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('To Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('invoice_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('invoice_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(function (Invoice $record) {
                        try {
                            return app(InvoiceService::class)->generateDownloadUrl($record);
                        } catch (\Throwable $e) {
                            Log::warning('Invoice PDF URL generation failed in admin', ['invoice_id' => $record->id]);
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record) => $record->status !== 'cancelled'),

                Action::make('cancel_invoice')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Invoice & Issue Credit Note')
                    ->modalDescription('This will cancel the invoice and automatically generate a credit note. This action cannot be undone.')
                    ->action(function (Invoice $record) {
                        try {
                            app(InvoiceService::class)->generateCreditNote($record, 'cancellation');
                        } catch (\Throwable $e) {
                            Log::error('Filament invoice cancellation failed', ['invoice_id' => $record->id, 'error' => $e->getMessage()]);
                        }
                    })
                    ->visible(fn (Invoice $record) => $record->status !== 'cancelled'),

                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view'   => ViewInvoice::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Invoices are immutable
    }
}
