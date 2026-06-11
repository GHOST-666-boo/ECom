<?php

namespace App\Filament\Resources\CorporateEnquiries\Pages;

use App\Filament\Resources\CorporateEnquiries\CorporateEnquiryResource;
use Filament\Resources\Pages\ListRecords;

class ListCorporateEnquiries extends ListRecords
{
    protected static string $resource = CorporateEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
