<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdatePageRequest;
use App\Models\Page;
use App\Services\Cms\HtmlSanitizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly HtmlSanitizerService $htmlSanitizerService,
    ) {}

    /**
     * Only index/edit/update — the fixed set of pages this project
     * needs (About, Why Invest, Formation Process, Requirements,
     * Privacy Policy, Terms and Conditions) are seeded via
     * PageContentSeeder, not admin-creatable in v1.
     */
    public function index(): View
    {
        $pages = Page::orderBy('id')->get();

        return view('dashboard.pages.index', ['pages' => $pages]);
    }

    public function edit(Page $page): View
    {
        return view('dashboard.pages.edit', ['page' => $page]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $page->update($this->sanitize($request->validated()));

        return redirect()
            ->route('dashboard.pages.edit', $page)
            ->with('status', __('dashboard.flash.page_updated'));
    }

    /**
     * body is allow-list sanitized as real HTML (reusing the same
     * HtmlSanitizerService/HTMLPurifier setup from the Articles task);
     * title/meta_title/meta_description have all markup stripped since
     * they must remain plain text.
     */
    private function sanitize(array $data): array
    {
        foreach (['ar', 'en'] as $locale) {
            if (isset($data['title'][$locale])) {
                $data['title'][$locale] = $this->htmlSanitizerService->stripAllTags($data['title'][$locale]);
            }

            if (isset($data['meta_title'][$locale])) {
                $data['meta_title'][$locale] = $this->htmlSanitizerService->stripAllTags($data['meta_title'][$locale]);
            }

            if (isset($data['meta_description'][$locale])) {
                $data['meta_description'][$locale] = $this->htmlSanitizerService->stripAllTags($data['meta_description'][$locale]);
            }

            if (isset($data['body'][$locale])) {
                $data['body'][$locale] = $this->htmlSanitizerService->sanitizeArticleBody($data['body'][$locale]);
            }
        }

        return $data;
    }
}
