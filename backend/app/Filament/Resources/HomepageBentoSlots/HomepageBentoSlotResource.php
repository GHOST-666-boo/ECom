<?php

namespace App\Filament\Resources\HomepageBentoSlots;

use App\Filament\Resources\HomepageBentoSlots\Pages\EditHomepageBentoSlot;
use App\Filament\Resources\HomepageBentoSlots\Pages\ListHomepageBentoSlots;
use App\Filament\Resources\HomepageBentoSlots\Schemas\HomepageBentoSlotForm;
use App\Filament\Resources\HomepageBentoSlots\Tables\HomepageBentoSlotsTable;
use App\Models\HomepageBentoSlot;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HomepageBentoSlotResource extends Resource
{
    protected static ?string $model = HomepageBentoSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Homepage Bento Slots';

    protected static ?string $modelLabel = 'Homepage Bento Slot';

    protected static string|UnitEnum|null $navigationGroup = 'Site Administration';

    public static function form(Schema $schema): Schema
    {
        return HomepageBentoSlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomepageBentoSlotsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomepageBentoSlots::route('/'),
            'edit' => EditHomepageBentoSlot::route('/{record}/edit'),
        ];
    }
}
