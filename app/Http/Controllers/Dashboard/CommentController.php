<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $comments = Comment::query()
            ->with('article')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('article_id'), fn ($query) => $query->where('article_id', $request->input('article_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.comments.index', [
            'comments' => $comments,
            'articles' => Article::orderByDesc('published_at')->get(),
        ]);
    }

    public function approve(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'approved']);

        return redirect()
            ->route('dashboard.comments.index')
            ->with('status', __('تم اعتماد التعليق، وأصبح ظاهرًا للعامة.'));
    }

    public function reject(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'rejected']);

        return redirect()
            ->route('dashboard.comments.index')
            ->with('status', __('تم رفض التعليق.'));
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return redirect()
            ->route('dashboard.comments.index')
            ->with('status', __('تم حذف التعليق.'));
    }
}
