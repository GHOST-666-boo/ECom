<?php

namespace App\Filament\Resources\ContactInquiries\Pages;

use App\Filament\Resources\ContactInquiries\ContactInquiryResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ViewContactInquiry extends EditRecord
{
    protected static string $resource = ContactInquiryResource::class;

    public function getTitle(): string
    {
        return 'Inquiry from ' . $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label($this->getRecord()->is_replied ? 'Reply Again' : 'Reply')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->form([
                    Textarea::make('reply_message')
                        ->label('Reply Message')
                        ->rows(5)
                        ->required()
                        ->placeholder('Type your reply here...'),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $replyMessage = $data['reply_message'];

                    try {
                        // Send the reply email
                        Mail::send('emails.contact-reply', [
                            'inquiry' => $record,
                            'replyMessage' => $replyMessage,
                        ], function ($message) use ($record) {
                            $message->to($record->email, $record->name)
                                ->subject('Re: ' . $record->subject);
                        });

                        // Update the record
                        $record->is_replied = true;
                        $record->reply_message = $replyMessage;
                        $record->replied_at = now();
                        $record->save();

                        Log::info('Replied to contact inquiry successfully', [
                            'inquiry_id' => $record->id,
                            'email' => $record->email,
                        ]);

                        Notification::make()
                            ->title('Reply sent successfully!')
                            ->success()
                            ->send();

                        $this->redirect(request()->header('Referer'));

                    } catch (\Exception $e) {
                        Log::error('Failed to send reply email', [
                            'error' => $e->getMessage(),
                            'inquiry_id' => $record->id,
                        ]);

                        Notification::make()
                            ->title('Failed to send reply email. Please check configuration.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Contact inquiries are read-only from the form edit page.
        // We only allow replies via the Reply header action.
        return $record;
    }

    protected function getFormActions(): array
    {
        // Remove default "Save" and "Cancel" buttons at the bottom of the page
        return [];
    }
}
