<?php

namespace App\Filament\Resources\ContactInquiries\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class ContactInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('name')
                    ->label('Name')
                    ->content(fn ($record) => $record?->name ?? '—'),

                Placeholder::make('email')
                    ->label('Email')
                    ->content(fn ($record) => $record?->email ?? '—'),

                Placeholder::make('phone')
                    ->label('Phone')
                    ->content(fn ($record) => $record?->phone ?? '—'),

                Placeholder::make('subject')
                    ->label('Subject')
                    ->content(fn ($record) => $record?->subject ?? '—'),

                Placeholder::make('created_at')
                    ->label('Submitted On')
                    ->content(fn ($record) => $record?->created_at?->format('d M Y, h:i A') ?? '—'),

                Placeholder::make('message')
                    ->label('Message')
                    ->columnSpanFull()
                    ->content(fn ($record) => $record?->message ?? '—'),

                Placeholder::make('reply_status')
                    ->label('Reply Status')
                    ->columnSpanFull()
                    ->content(fn ($record) => $record?->is_replied 
                        ? "Replied on " . $record->replied_at?->format('d M Y, h:i A')
                        : "No reply sent yet"),

                Placeholder::make('reply_message')
                    ->label('Reply Message')
                    ->columnSpanFull()
                    ->visible(fn ($record) => (bool) $record?->is_replied)
                    ->content(fn ($record) => $record?->reply_message ?? '—'),
            ]);
    }
}
