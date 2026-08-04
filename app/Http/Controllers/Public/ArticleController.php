<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->get();

        return view('public.articles.index', ['articles' => $articles]);
    }

    public function show(Article $article): View
    {
        abort_unless(
            $article->is_published && $article->published_at?->lessThanOrEqualTo(now()),
            404
        );

        return view('public.articles.show', [
            'article' => $article,
            // Oldest first: a flat, non-threaded comment section reads
            // more naturally as a chronological conversation from first
            // to last on an informational/corporate blog like this one,
            // rather than a social-feed "newest first" pattern.
            'comments' => $article->comments()->approved()->oldest()->get(),
        ]);
    }
}
