<?php

namespace App\Filament\Resources\ContactInquiries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactInquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                IconColumn::make('is_replied')
                    ->label('Replied')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_replied')
                    ->label('Reply Status')
                    ->placeholder('All inquiries')
                    ->trueLabel('Replied only')
                    ->falseLabel('Unreplied only'),
            ])
            ->recordActions([
                EditAction::make()->label('View / Reply'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
