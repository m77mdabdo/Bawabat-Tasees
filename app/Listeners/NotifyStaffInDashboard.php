<?php

namespace App\Listeners;

use App\Events\LeadSubmitted;
use App\Filament\Resources\Leads\LeadResource;
use App\Leads\LeadPresenter;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Puts a new enquiry in the dashboard bell of every active staff member who handles enquiries.
 * The panel polls every 5 seconds, so it appears while they are working. Each person reads it
 * in their own dashboard language.
 */
final class NotifyStaffInDashboard
{
    public function handle(LeadSubmitted $event): void
    {
        $lead = $event->lead;

        try {
            $staff = User::query()->where('is_active', true)->with('roles.permissions')->get()
                ->filter(fn (User $u) => $u->can('leads.view'));

            foreach ($staff as $user) {
                $locale = $user->dashboardLocale();
                Notification::make()
                    ->title(LeadPresenter::kind($lead, $locale).' '.$lead->reference())
                    ->body($lead->full_name.($lead->requestedService ? ' · '.$lead->requestedService->getTranslation('name', $locale) : ''))
                    ->icon($lead->isContactMessage() ? 'heroicon-o-envelope' : 'heroicon-o-inbox-arrow-down')
                    ->iconColor('warning')
                    ->actions([
                        Action::make('open')->label(__('Open', [], $locale))->button()
                            ->url(LeadResource::getUrl('view', ['record' => $lead], panel: 'dashboard'))->markAsRead(),
                    ])
                    ->sendToDatabase($user);
            }
        } catch (Throwable $e) {
            Log::warning('Dashboard notification for lead failed', ['lead' => $lead->getKey(), 'error' => $e->getMessage()]);
        }
    }
}
