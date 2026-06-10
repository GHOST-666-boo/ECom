<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── Read-only order info ──────────────────────────────────────
                Placeholder::make('order_number')
                    ->label('Order Number')
                    ->content(fn ($record) => $record?->order_number ?? '—'),

                Placeholder::make('status')
                    ->label('Current Status')
                    ->content(fn ($record) => ucfirst($record?->status ?? '—')),

                Placeholder::make('payment_method')
                    ->label('Payment Method')
                    ->content(fn ($record) => match ($record?->payment_method) {
                        'cod'      => 'Cash on Delivery',
                        'razorpay' => 'Online (Razorpay)',
                        default    => '—',
                    }),

                Placeholder::make('payment_status')
                    ->label('Payment Status')
                    ->content(fn ($record) => ucfirst($record?->payment_status ?? '—')),

                Placeholder::make('total')
                    ->label('Total Amount')
                    ->content(fn ($record) => $record
                        ? '₹' . number_format($record->total, 2)
                        : '—'),

                Placeholder::make('created_at')
                    ->label('Placed On')
                    ->content(fn ($record) => $record?->created_at?->format('d M Y, h:i A') ?? '—'),

                // ── Editable tracking fields ──────────────────────────────────
                TextInput::make('tracking_number')
                    ->label('AWB / Tracking Number')
                    ->maxLength(100)
                    ->placeholder('e.g. DL1234567890'),

                TextInput::make('courier_name')
                    ->label('Courier Name')
                    ->maxLength(100)
                    ->placeholder('e.g. Delhivery, BlueDart, DTDC'),

                // ── Delivery address ──────────────────────────────────────────
                Placeholder::make('delivery_address')
                    ->label('Delivery Address')
                    ->columnSpanFull()
                    ->content(function ($record) {
                        if (!$record?->address_snapshot) return '—';
                        $a = $record->address_snapshot;
                        $phone = $a['phone'] ?? null;
                        $parts = array_filter([
                            $a['name']    ?? null,
                            $phone ? '📞 ' . $phone : null,
                            $a['line1']   ?? null,
                            $a['line2']   ?? null,
                            ($a['city'] ?? '') . ', ' . ($a['state'] ?? '') . ' - ' . ($a['pincode'] ?? ''),
                        ]);
                        return new HtmlString(implode('<br>', $parts));
                    }),

                // ── Order items with product images ───────────────────────────
                Placeholder::make('order_items')
                    ->label('Order Items')
                    ->columnSpanFull()
                    ->content(function ($record) {
                        if (!$record) return '—';

                        $record->loadMissing('orderItems.product');

                        $rows = '';
                        foreach ($record->orderItems as $item) {
                            $product  = $item->product;
                            $imgUrl   = $product?->image_urls[0] ?? null;
                            $imgTag   = $imgUrl
                                ? "<img src=\"{$imgUrl}\" 
                                        style=\"width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,0.1);\">"
                                : "<div style=\"width:56px;height:56px;border-radius:8px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;font-size:1.4rem;\">🖼</div>";

                            $name  = e($product?->name ?? 'Product #' . $item->product_id);
                            $price = '₹' . number_format($item->price, 2);
                            $qty   = $item->quantity;
                            $total = '₹' . number_format($item->price * $qty, 2);

                            $rows .= "
                                <tr style=\"border-bottom:1px solid rgba(255,255,255,0.07);\">
                                    <td style=\"padding:12px 8px;\">{$imgTag}</td>
                                    <td style=\"padding:12px 8px;font-weight:600;\">{$name}</td>
                                    <td style=\"padding:12px 8px;color:#9ca3af;\">{$price}</td>
                                    <td style=\"padding:12px 8px;text-align:center;\">{$qty}</td>
                                    <td style=\"padding:12px 8px;font-weight:700;color:#f97316;text-align:right;\">{$total}</td>
                                </tr>
                            ";
                        }

                        return new HtmlString("
                            <table style=\"width:100%;border-collapse:collapse;font-size:0.88rem;\">
                                <thead>
                                    <tr style=\"border-bottom:2px solid rgba(255,255,255,0.1);\">
                                        <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">Image</th>
                                        <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">Product</th>
                                        <th style=\"padding:8px;text-align:left;color:#9ca3af;font-weight:600;\">Unit Price</th>
                                        <th style=\"padding:8px;text-align:center;color:#9ca3af;font-weight:600;\">Qty</th>
                                        <th style=\"padding:8px;text-align:right;color:#9ca3af;font-weight:600;\">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>{$rows}</tbody>
                            </table>
                        ");
                    }),
            ]);
    }
}
