<?php

namespace App\Filament\Resources\HomepageBentoSlots\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomepageBentoSlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slot_key')
                    ->label('Slot')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('badge')
                    ->label('Badge')
                    ->placeholder('-'),

                TextColumn::make('theme')
                    ->label('Theme')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'light' => 'gray',
                        'dark' => 'info',
                        'gradient' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('link_type')
                    ->label('Link Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'none' => 'gray',
                        'category' => 'success',
                        'product' => 'warning',
                        'custom' => 'info',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('slot_key', 'asc');
    }
}
