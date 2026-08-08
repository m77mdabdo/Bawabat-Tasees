<?php

namespace App\Services\Crm;

use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Reads and writes the Contact Messages inbox.
 *
 * There is no contact_messages table: contact submissions are `leads`
 * rows with type = 'contact', written by ContactController@store. This
 * service is the narrow view of that table, not a second store.
 */
class ContactMessageService
{
    /**
     * @param  'all'|'unhandled'|'handled'  $filter
     */
    public function paginate(string $filter = 'all', int $perPage = 20): LengthAwarePaginator
    {
        return Lead::query()
            ->contactMessages()
            ->when($filter === 'unhandled', fn ($query) => $query->unhandled())
            ->when($filter === 'handled', fn ($query) => $query->where('is_handled', true))
            // Unread first, then newest — the inbox is a work queue.
            ->orderBy('is_handled')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Drives the sidebar badge, so it runs on EVERY dashboard page
     * render — covered by leads_type_is_handled_index.
     */
    public function unhandledCount(): int
    {
        return Lead::query()->contactMessages()->unhandled()->count();
    }

    /**
     * is_handled is intentionally not fillable (see the Lead model), so
     * this sets it explicitly rather than through mass assignment.
     */
    public function setHandled(Lead $lead, bool $handled): Lead
    {
        $lead->is_handled = $handled;
        $lead->save();

        return $lead;
    }
}
