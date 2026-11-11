<?php

namespace App\Filament\Resources\NotificationRecipients\Pages;

use App\Filament\Resources\NotificationRecipients\NotificationRecipientResource;
use App\Mail\RecipientAdded;
use App\Models\NotificationRecipient;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ManageNotificationRecipients extends ManageRecords
{
    protected static string $resource = NotificationRecipientResource::class;

    public function getSubheading(): ?string
    {
        return __('Each address gets its own instant email for every new enquiry. Replying to it answers the client.');
    }

    protected function getHeaderActions(): array
    {
        // A new address is told straight away what it will receive and who added it.
        return [CreateAction::make()->after(function (NotificationRecipient $record) {
            try {
                Mail::to($record->email, $record->name)->send(new RecipientAdded($record, (string) auth()->user()?->name));
            } catch (Throwable $e) {
                report($e);
                Notification::make()->warning()->title(__('Added, but the welcome email could not be sent'))->body($e->getMessage())->send();
            }
        })];
    }
}
