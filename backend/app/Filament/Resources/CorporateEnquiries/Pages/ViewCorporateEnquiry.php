<?php

namespace App\Filament\Resources\CorporateEnquiries\Pages;

use App\Filament\Resources\CorporateEnquiries\CorporateEnquiryResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class ViewCorporateEnquiry extends EditRecord
{
    protected static string $resource = CorporateEnquiryResource::class;

    public function getTitle(): string
    {
        return 'Enquiry from ' . $this->getRecord()->company_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('update_status')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->form([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'new'       => 'New',
                            'contacted' => 'Contacted',
                            'closed'    => 'Closed',
                        ])
                        ->default(fn () => $this->getRecord()->status)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $record->status = $data['status'];
                    $record->save();

                    Notification::make()
                        ->title('Status updated successfully!')
                        ->success()
                        ->send();

                    $this->redirect(request()->header('Referer'));
                }),
        ];
    }

    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Corporate enquiries are read-only from the form edit page.
        return $record;
    }

    protected function getFormActions(): array
    {
        // Remove default "Save" and "Cancel" buttons at the bottom of the page
        return [];
    }
}
