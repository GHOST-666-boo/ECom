<?php

namespace App\Filament\Resources\CorporateEnquiries\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class CorporateEnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('company_name')
                    ->label('Company Name')
                    ->content(fn ($record) => $record?->company_name ?? '—'),

                Placeholder::make('company_email')
                    ->label('Company Email')
                    ->content(fn ($record) => $record?->company_email ?? '—'),

                Placeholder::make('contact_number')
                    ->label('Contact Number')
                    ->content(fn ($record) => $record?->contact_number ?? '—'),

                Placeholder::make('categories')
                    ->label('Categories')
                    ->content(fn ($record) => $record?->categories
                        ? implode(', ', $record->categories)
                        : '—'),

                Placeholder::make('status')
                    ->label('Status')
                    ->content(fn ($record) => ucfirst($record?->status ?? '—')),

                Placeholder::make('created_at')
                    ->label('Submitted On')
                    ->content(fn ($record) => $record?->created_at?->format('d M Y, h:i A') ?? '—'),

                Placeholder::make('message')
                    ->label('Message')
                    ->columnSpanFull()
                    ->content(fn ($record) => $record?->message ?? '—'),
            ]);
    }
}
