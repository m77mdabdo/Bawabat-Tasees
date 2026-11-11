<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Reports;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\NotificationRecipients\NotificationRecipientResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Comment;
use App\Models\Lead;
use Filament\Widgets\Widget;

/** Greeting, what is waiting today, and quick actions; each action appears only to staff allowed to use it. */
class WelcomeWidget extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.welcome';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $now = now('Asia/Riyadh');
        $canLeads = (bool) $user?->can('leads.view');

        $actions = array_values(array_filter([
            $canLeads ? ['label' => __('Review enquiries'), 'hint' => __('Consultations and messages'), 'url' => LeadResource::getUrl(), 'icon' => 'heroicon-o-inbox-stack'] : null,
            ServiceResource::canViewAny() ? ['label' => __('Manage services'), 'hint' => __('What the site offers'), 'url' => ServiceResource::getUrl(), 'icon' => 'heroicon-o-squares-2x2'] : null,
            ArticleResource::canCreate() ? ['label' => __('Write an article'), 'hint' => __('Blog and news'), 'url' => ArticleResource::getUrl('create'), 'icon' => 'heroicon-o-pencil-square'] : null,
            CommentResource::canViewAny() ? ['label' => __('Moderate comments'), 'hint' => __('Approve or reject'), 'url' => CommentResource::getUrl(), 'icon' => 'heroicon-o-chat-bubble-left-ellipsis'] : null,
            Reports::canAccess() ? ['label' => __('Reports'), 'hint' => __('Leads, conversions, ROI'), 'url' => Reports::getUrl(), 'icon' => 'heroicon-o-chart-bar'] : null,
            NotificationRecipientResource::canViewAny() ? ['label' => __('Enquiry email alerts'), 'hint' => __('Who gets notified'), 'url' => NotificationRecipientResource::getUrl(), 'icon' => 'heroicon-o-bell-alert'] : null,
        ]));

        return [
            'greeting' => __($now->hour < 12 ? 'Good morning, :name' : 'Good evening, :name', ['name' => strtok((string) $user?->name, ' ')]),
            'date' => $now->locale(app()->getLocale())->translatedFormat('l j F Y'),
            'unhandled' => $canLeads ? Lead::query()->unhandled()->count() : null,
            'today' => $canLeads ? Lead::query()->where('created_at', '>=', $now->copy()->startOfDay()->utc())->count() : null,
            'pendingComments' => CommentResource::canViewAny() ? Comment::query()->pending()->count() : null,
            'leadsUrl' => $canLeads ? LeadResource::getUrl('index', ['tab' => 'unhandled']) : null,
            'actions' => $actions,
        ];
    }
}
