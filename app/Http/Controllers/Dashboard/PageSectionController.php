<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StorePageSectionRequest;
use App\Http\Requests\Dashboard\UpdatePageSectionRequest;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageSectionController extends Controller
{
    public function index(Page $page): View
    {
        $sections = $page->sections()->orderBy('sort_order')->get();

        return view('dashboard.pages.sections.index', ['page' => $page, 'sections' => $sections]);
    }

    public function create(Page $page): View
    {
        return view('dashboard.pages.sections.create', ['page' => $page]);
    }

    public function store(StorePageSectionRequest $request, Page $page): RedirectResponse
    {
        $page->sections()->create($this->toAttributes($request->validated()));

        return redirect()
            ->route('dashboard.pages.sections.index', $page)
            ->with('status', __('dashboard.flash.page_section_created'));
    }

    public function edit(Page $page, PageSection $section): View
    {
        return view('dashboard.pages.sections.edit', ['page' => $page, 'section' => $section]);
    }

    public function update(UpdatePageSectionRequest $request, Page $page, PageSection $section): RedirectResponse
    {
        $section->update($this->toAttributes($request->validated()));

        return redirect()
            ->route('dashboard.pages.sections.index', $page)
            ->with('status', __('dashboard.flash.page_section_updated'));
    }

    public function destroy(Page $page, PageSection $section): RedirectResponse
    {
        $section->delete();

        return redirect()
            ->route('dashboard.pages.sections.index', $page)
            ->with('status', __('dashboard.flash.page_section_deleted'));
    }

    /**
     * The form posts title/description/icon as top-level fields, but they
     * are stored nested inside the page_sections.content JSON column —
     * repackage validated input into that shape before persisting.
     */
    private function toAttributes(array $validated): array
    {
        return [
            'key' => $validated['key'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? false,
            'content' => [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'icon' => $validated['icon'] ?? null,
            ],
        ];
    }
}
