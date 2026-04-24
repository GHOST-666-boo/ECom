<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class ViewOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    // Override title
    public function getTitle(): string
    {
        return 'Order ' . $this->getRecord()->order_number;
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Update Status action ──────────────────────────────────────────
            Action::make('updateStatus')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form(function () {
                    $record = $this->getRecord();

                    $validNext = [
                        'pending'    => ['confirmed', 'cancelled'],
                        'confirmed'  => ['processing', 'cancelled'],
                        'processing' => ['shipped', 'cancelled'],
                        'shipped'    => ['delivered'],
                        'delivered'  => [],
                        'cancelled'  => [],
                    ][$record->status] ?? [];

                    $options = array_combine(
                        $validNext,
                        array_map('ucfirst', $validNext)
                    );

                    return [
                        Select::make('status')
                            ->label('New Status')
                            ->options($options)
                            ->required()
                            ->live()
                            ->helperText('Current: ' . ucfirst($record->status)),

                        TextInput::make('tracking_number')
                            ->label('AWB / Tracking Number')
                            ->placeholder('e.g. DL1234567890')
                            ->maxLength(100)
                            ->required(fn ($get) => $get('status') === 'shipped')
                            ->visible(fn ($get) => $get('status') === 'shipped')
                            ->default($record->tracking_number),

                        TextInput::make('courier_name')
                            ->label('Courier Name')
                            ->placeholder('e.g. Delhivery, BlueDart, DTDC')
                            ->maxLength(100)
                            ->visible(fn ($get) => $get('status') === 'shipped')
                            ->default($record->courier_name),
                    ];
                })
                ->action(function (array $data) {
                    $record    = $this->getRecord();
                    $newStatus = $data['status'];

                    if ($newStatus === 'shipped') {
                        $record->tracking_number = $data['tracking_number'];
                        $record->courier_name    = $data['courier_name'] ?? null;
                    }

                    if ($newStatus === 'delivered' && $record->payment_method === 'cod') {
                        $record->payment_status = 'paid';
                    }

                    $record->status = $newStatus;
                    $record->save();

                    // Fire notification events
                    if ($newStatus === 'shipped') {
                        OrderShipped::dispatch($record);
                    } elseif ($newStatus === 'delivered') {
                        OrderDelivered::dispatch($record);
                    } elseif ($newStatus === 'cancelled') {
                        OrderCancelled::dispatch($record, 'admin cancellation');
                    }

                    Log::info('Order status updated via Filament admin', [
                        'order_id'   => $record->id,
                        'new_status' => $newStatus,
                    ]);

                    Notification::make()
                        ->title('Status updated to: ' . ucfirst($newStatus))
                        ->success()
                        ->send();

                    // Reload the page to reflect changes
                    $this->redirect(request()->header('Referer'));
                })
                ->visible(fn () => !in_array(
                    $this->getRecord()->status,
                    ['delivered', 'cancelled']
                )),
        ];
    }

    // Save only tracking_number and courier_name from the form
    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $record->tracking_number = $data['tracking_number'] ?? $record->tracking_number;
        $record->courier_name    = $data['courier_name']    ?? $record->courier_name;
        $record->save();

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Tracking info saved successfully!';
    }
}
