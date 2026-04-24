<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'confirmed'  => 'info',
                        'processing' => 'purple',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cod'      => 'warning',
                        'razorpay' => 'success',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cod'      => 'COD',
                        'razorpay' => 'Online',
                        default    => $state,
                    }),

                TextColumn::make('payment_status')
                    ->label('Paid?')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'pending' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('total')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('tracking_number')
                    ->label('AWB')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Placed On')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'confirmed'  => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'delivered'  => 'Delivered',
                        'cancelled'  => 'Cancelled',
                    ])
                    ->placeholder('All Statuses'),

                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cod'      => 'Cash on Delivery',
                        'razorpay' => 'Online (Razorpay)',
                    ])
                    ->placeholder('All Methods'),

                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid'    => 'Paid',
                    ])
                    ->placeholder('All'),
            ])
            ->recordActions([
                EditAction::make()->label('View / Edit'),

                // Quick status update action
                Action::make('updateStatus')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form(function ($record) {
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

                        $fields = [
                            Select::make('status')
                                ->label('New Status')
                                ->options($options)
                                ->required()
                                ->live(),
                        ];

                        // Show tracking fields only when shipping
                        if (in_array('shipped', $validNext)) {
                            $fields[] = TextInput::make('tracking_number')
                                ->label('AWB / Tracking Number')
                                ->placeholder('e.g. DL1234567890')
                                ->required(fn ($get) => $get('status') === 'shipped')
                                ->maxLength(100)
                                ->visible(fn ($get) => $get('status') === 'shipped');

                            $fields[] = TextInput::make('courier_name')
                                ->label('Courier Name')
                                ->placeholder('e.g. Delhivery, BlueDart')
                                ->maxLength(100)
                                ->visible(fn ($get) => $get('status') === 'shipped');
                        }

                        return $fields;
                    })
                    ->action(function ($record, array $data) {
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
                    })
                    ->visible(fn ($record) => !in_array($record->status, ['delivered', 'cancelled']))
                    ->requiresConfirmation(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
