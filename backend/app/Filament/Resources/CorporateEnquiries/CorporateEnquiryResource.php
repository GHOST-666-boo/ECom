<?php

namespace App\Filament\Resources\CorporateEnquiries;

use App\Filament\Resources\CorporateEnquiries\Pages\ListCorporateEnquiries;
use App\Filament\Resources\CorporateEnquiries\Pages\ViewCorporateEnquiry;
use App\Filament\Resources\CorporateEnquiries\Schemas\CorporateEnquiryForm;
use App\Filament\Resources\CorporateEnquiries\Tables\CorporateEnquiriesTable;
use App\Models\CorporateEnquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CorporateEnquiryResource extends Resource
{
    protected static ?string $model = CorporateEnquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Corporate Enquiries';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return CorporateEnquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CorporateEnquiriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCorporateEnquiries::route('/'),
            'edit'  => ViewCorporateEnquiry::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Corporate enquiries are created by users via the corporate gifting page
    }
}
