<?php

namespace App\Filament\Resources\HomepageBentoSlots\Pages;

use App\Filament\Resources\HomepageBentoSlots\HomepageBentoSlotResource;
use Filament\Resources\Pages\EditRecord;

class EditHomepageBentoSlot extends EditRecord
{
    protected static string $resource = HomepageBentoSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
