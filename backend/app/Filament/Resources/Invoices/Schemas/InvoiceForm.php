<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Product;
use App\Rules\ValidGstin;
use App\Rules\ValidHsnCode;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Buyer Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('order_id')
                            ->label('Select Order (Optional)')
                            ->relationship('order', 'order_number')
                            ->placeholder('Select an existing Order')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $order = \App\Models\Order::find($state);
                                    if ($order) {
                                        $snapshot = $order->address_snapshot ?? [];
                                        $set('buyer_name', $snapshot['name'] ?? ($order->user->name ?? ''));
                                        $set('buyer_state', $snapshot['state'] ?? 'Delhi');
                                        
                                        // Format address
                                        $addressParts = array_filter([
                                            $snapshot['line1']   ?? null,
                                            $snapshot['line2']   ?? null,
                                            $snapshot['city']    ?? null,
                                            $snapshot['state']   ?? null,
                                            $snapshot['pincode'] ?? null,
                                        ]);
                                        $set('buyer_address', implode(', ', $addressParts));
                                    }
                                }
                            }),

                        Select::make('invoice_type')
                            ->options([
                                'B2C' => 'B2C (Consumer)',
                                'B2B' => 'B2B (Business)',
                            ])
                            ->default('B2C')
                            ->required()
                            ->live(),
                        
                        TextInput::make('buyer_name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('buyer_gstin')
                            ->label('Buyer GSTIN')
                            ->required(fn ($get) => $get('invoice_type') === 'B2B')
                            ->visible(fn ($get) => $get('invoice_type') === 'B2B')
                            ->rule(new ValidGstin())
                            ->maxLength(15),

                        Select::make('buyer_state')
                            ->options(self::getIndianStates())
                            ->default('Delhi')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::recalculateTotals($set, $get);
                            }),

                        DatePicker::make('invoice_date')
                            ->default(now())
                            ->required(),

                        Textarea::make('buyer_address')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Invoice Items')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Select Product (Option A)')
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(4)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $set('product_name', $product->name);
                                                        $set('unit_price', $product->price);
                                                        
                                                        $category = $product->category;
                                                        $hsn = $product->hsn_code ?? $category?->hsn_code ?? '';
                                                        $gst = $product->gst_rate ?? $category?->gst_rate ?? 0;
                                                        
                                                        $set('hsn_code', $hsn);
                                                        $set('gst_rate', $gst);
                                                    }
                                                }
                                            }),

                                        TextInput::make('product_name')
                                            ->label('Product Name (Option B / Custom)')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(4)
                                            ->live(onBlur: true),

                                        TextInput::make('hsn_code')
                                            ->label('HSN Code')
                                            ->required()
                                            ->rule(new ValidHsnCode())
                                            ->columnSpan(4)
                                            ->live(onBlur: true),

                                        TextInput::make('gst_rate')
                                            ->label('GST Rate (%)')
                                            ->numeric()
                                            ->required()
                                            ->suffix('%')
                                            ->columnSpan(2)
                                            ->live(onBlur: true),

                                        TextInput::make('unit_price')
                                            ->label('Price (Inclusive GST)')
                                            ->numeric()
                                            ->required()
                                            ->prefix('₹')
                                            ->columnSpan(3)
                                            ->live(onBlur: true),

                                        TextInput::make('quantity')
                                            ->label('Qty')
                                            ->numeric()
                                            ->integer()
                                            ->required()
                                            ->default(1)
                                            ->columnSpan(2)
                                            ->live(onBlur: true),

                                        TextInput::make('taxable_value')
                                            ->label('Taxable Value')
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix('₹')
                                            ->columnSpan(5),

                                        TextInput::make('cgst_amount')
                                            ->label('CGST')
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix('₹')
                                            ->columnSpan(3),

                                        TextInput::make('sgst_amount')
                                            ->label('SGST')
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix('₹')
                                            ->columnSpan(3),

                                        TextInput::make('igst_amount')
                                            ->label('IGST')
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix('₹')
                                            ->columnSpan(3),

                                        TextInput::make('line_total')
                                            ->label('Total')
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefix('₹')
                                            ->columnSpan(3),
                                    ]),
                            ])
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::recalculateTotals($set, $get);
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Totals')
                    ->columns(5)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Taxable Value')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('₹'),

                        TextInput::make('cgst')
                            ->label('Total CGST')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('₹'),

                        TextInput::make('sgst')
                            ->label('Total SGST')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('₹'),

                        TextInput::make('igst')
                            ->label('Total IGST')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('₹'),

                        TextInput::make('total_amount')
                            ->label('Grand Total')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('₹'),
                    ]),
            ]);
    }

    public static function recalculateTotals(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];
        $buyerState = $get('buyer_state') ?? 'Delhi';
        $sellerState = config('invoice.seller_state', 'Delhi');
        $isIntraState = (strtolower(trim($buyerState)) === strtolower(trim($sellerState)));

        $subtotal = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalIgst = 0;
        $totalAmount = 0;

        foreach ($items as $key => $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $gstRate = (float) ($item['gst_rate'] ?? 0);

            $lineTotal = round($qty * $price, 2);
            $taxableValue = round($lineTotal / (1 + ($gstRate / 100)), 2);
            $gstAmount = round($lineTotal - $taxableValue, 2);

            if ($isIntraState) {
                $cgst = round($gstAmount / 2, 2);
                $sgst = round($gstAmount / 2, 2);
                $igst = 0;
                $taxableValue = round($lineTotal - ($cgst + $sgst), 2);
            } else {
                $cgst = 0;
                $sgst = 0;
                $igst = $gstAmount;
                $taxableValue = round($lineTotal - $igst, 2);
            }

            $items[$key]['taxable_value'] = $taxableValue;
            $items[$key]['cgst_amount'] = $cgst;
            $items[$key]['sgst_amount'] = $sgst;
            $items[$key]['igst_amount'] = $igst;
            $items[$key]['line_total'] = $lineTotal;

            $subtotal += $taxableValue;
            $totalCgst += $cgst;
            $totalSgst += $sgst;
            $totalIgst += $igst;
            $totalAmount += $lineTotal;
        }

        $set('items', $items);
        $set('subtotal', round($subtotal, 2));
        $set('cgst', round($totalCgst, 2));
        $set('sgst', round($totalSgst, 2));
        $set('igst', round($totalIgst, 2));
        $set('total_amount', round($totalAmount, 2));
    }

    public static function getIndianStates(): array
    {
        return [
            'Andhra Pradesh' => 'Andhra Pradesh',
            'Arunachal Pradesh' => 'Arunachal Pradesh',
            'Assam' => 'Assam',
            'Bihar' => 'Bihar',
            'Chhattisgarh' => 'Chhattisgarh',
            'Goa' => 'Goa',
            'Gujarat' => 'Gujarat',
            'Haryana' => 'Haryana',
            'Himachal Pradesh' => 'Himachal Pradesh',
            'Jharkhand' => 'Jharkhand',
            'Karnataka' => 'Karnataka',
            'Kerala' => 'Kerala',
            'Madhya Pradesh' => 'Madhya Pradesh',
            'Maharashtra' => 'Maharashtra',
            'Manipur' => 'Manipur',
            'Meghalaya' => 'Meghalaya',
            'Mizoram' => 'Mizoram',
            'Nagaland' => 'Nagaland',
            'Odisha' => 'Odisha',
            'Punjab' => 'Punjab',
            'Rajasthan' => 'Rajasthan',
            'Sikkim' => 'Sikkim',
            'Tamil Nadu' => 'Tamil Nadu',
            'Telangana' => 'Telangana',
            'Tripura' => 'Tripura',
            'Uttar Pradesh' => 'Uttar Pradesh',
            'Uttarakhand' => 'Uttarakhand',
            'West Bengal' => 'West Bengal',
            'Andaman and Nicobar Islands' => 'Andaman and Nicobar Islands',
            'Chandigarh' => 'Chandigarh',
            'Dadra and Nagar Haveli and Daman and Diu' => 'Dadra and Nagar Haveli and Daman and Diu',
            'Delhi' => 'Delhi',
            'Jammu and Kashmir' => 'Jammu and Kashmir',
            'Ladakh' => 'Ladakh',
            'Lakshadweep' => 'Lakshadweep',
            'Puducherry' => 'Puducherry',
        ];
    }
}
