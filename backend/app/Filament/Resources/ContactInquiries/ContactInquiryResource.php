<?php

namespace App\Filament\Resources\ContactInquiries;

use App\Filament\Resources\ContactInquiries\Pages\ListContactInquiries;
use App\Filament\Resources\ContactInquiries\Pages\ViewContactInquiry;
use App\Filament\Resources\ContactInquiries\Schemas\ContactInquiryForm;
use App\Filament\Resources\ContactInquiries\Tables\ContactInquiriesTable;
use App\Models\ContactInquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContactInquiryResource extends Resource
{
    protected static ?string $model = ContactInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Contact Inquiries';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ContactInquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactInquiriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactInquiries::route('/'),
            'edit'  => ViewContactInquiry::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Contact inquiries are created by users via contact page
    }
}
