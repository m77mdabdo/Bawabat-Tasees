<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreArticleRequest;
use App\Http\Requests\Dashboard\UpdateArticleRequest;
use App\Models\Article;
use App\Services\Cms\ContentPublishingService;
use App\Services\Cms\HtmlSanitizerService;
use App\Services\Seo\SeoMetaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ContentPublishingService $contentPublishingService,
        private readonly HtmlSanitizerService $htmlSanitizerService,
        private readonly SeoMetaService $seoMetaService,
    ) {}

    public function index(): View
    {
        $articles = Article::orderBy('created_at', 'desc')->paginate(20);

        return view('dashboard.articles.index', ['articles' => $articles]);
    }

    public function create(): View
    {
        return view('dashboard.articles.create');
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $data = $this->sanitize($request->safe()->except(['cover_image', 'seo', 'seo_og_image']));

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->contentPublishingService->storeImage(
                $request->file('cover_image'),
                'articles'
            );
        }

        $article = Article::create($data);

        $this->seoMetaService->persist(
            $article,
            $request->validated('seo', []),
            $request->file('seo_og_image')
        );

        return redirect()
            ->route('dashboard.articles.index')
            ->with('status', __('dashboard.flash.article_created'));
    }

    public function edit(Article $article): View
    {
        return view('dashboard.articles.edit', ['article' => $article]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $data = $this->sanitize($request->safe()->except(['cover_image', 'seo', 'seo_og_image']));

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->contentPublishingService->replaceImage(
                $request->file('cover_image'),
                'articles',
                $article->cover_image
            );
        }

        $article->update($data);

        $this->seoMetaService->persist(
            $article,
            $request->validated('seo', []),
            $request->file('seo_og_image')
        );

        return redirect()
            ->route('dashboard.articles.index')
            ->with('status', __('dashboard.flash.article_updated'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()
            ->route('dashboard.articles.index')
            ->with('status', __('dashboard.flash.article_deleted'));
    }

    /**
     * Sanitize translatable content before it is ever persisted: body is
     * allow-list sanitized as real HTML, while title/excerpt have all
     * markup stripped since they must remain plain text. This runs here
     * rather than in the Form Request so the same "validated input becomes
     * final persisted data" transformation step used for cover_image
     * (via ContentPublishingService) covers body sanitization too.
     */
    private function sanitize(array $data): array
    {
        foreach (['ar', 'en'] as $locale) {
            if (isset($data['title'][$locale])) {
                $data['title'][$locale] = $this->htmlSanitizerService->stripAllTags($data['title'][$locale]);
            }

            if (isset($data['excerpt'][$locale])) {
                $data['excerpt'][$locale] = $this->htmlSanitizerService->stripAllTags($data['excerpt'][$locale]);
            }

            if (isset($data['body'][$locale])) {
                $data['body'][$locale] = $this->htmlSanitizerService->sanitizeArticleBody($data['body'][$locale]);
            }
        }

        return $data;
    }
}
