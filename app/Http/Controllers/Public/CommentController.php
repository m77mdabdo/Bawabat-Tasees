<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreCommentRequest;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Article $article): RedirectResponse
    {
        // Same rules as the article's own public show() action — no
        // commenting on an unpublished or not-yet-published article via
        // a direct POST, even though the form itself is never rendered
        // for one.
        abort_unless(
            $article->is_published && $article->published_at?->lessThanOrEqualTo(now()),
            404
        );

        // Honeypot check happens before any DB write — identical pattern
        // to ConsultationController@store/ContactController@store: a
        // filled website_url gets the exact same success response so a
        // bot never learns it was caught, but no Comment row is created.
        if ($request->filled('website_url')) {
            return redirect()
                ->to(lroute('articles.show', $article))
                ->with('status', __('site.flash.comment_submitted'));
        }

        // status is never taken from the request — every public
        // submission is force-created as 'pending' regardless of
        // anything in the payload, so there is no way to submit an
        // already-approved comment.
        Comment::create([
            ...$request->safe()->only(['name', 'email', 'body']),
            'article_id' => $article->id,
            'status' => 'pending',
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->to(lroute('articles.show', $article))
            ->with('status', __('site.flash.comment_submitted'));
    }
}
