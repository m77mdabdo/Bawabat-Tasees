<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\Crm\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function __construct(
        private readonly ContactMessageService $contactMessages,
    ) {}

    public function index(Request $request): View
    {
        // Whitelisted rather than passed through: the filter reaches a
        // query builder.
        $filter = in_array($request->query('filter'), ['unhandled', 'handled'], true)
            ? $request->query('filter')
            : 'all';

        return view('dashboard.contact-messages.index', [
            'messages' => $this->contactMessages->paginate($filter),
            'filter' => $filter,
        ]);
    }

    /**
     * Route-model-bound to Lead, so it has to refuse a consultation
     * lead: this action must not become a back door for flipping state
     * on rows the inbox never shows.
     */
    public function handle(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->type === Lead::TYPE_CONTACT, 404);

        $this->contactMessages->setHandled($lead, ! $request->boolean('unhandle'));

        return redirect()
            ->back()
            ->with('status', __('dashboard.flash.contact_message_handled'));
    }
}
