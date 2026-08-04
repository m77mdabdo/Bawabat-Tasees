<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreMediaRequest;
use App\Models\Media;
use App\Services\Cms\MediaLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaLibraryService $mediaLibraryService
    ) {}

    public function index(): View
    {
        $media = Media::orderBy('created_at', 'desc')->paginate(24);

        return view('dashboard.media.index', ['media' => $media]);
    }

    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $this->mediaLibraryService->upload(
            $request->file('file'),
            $request->validated('alt_text'),
            $request->user()
        );

        return redirect()
            ->route('dashboard.media.index')
            ->with('status', 'Media uploaded.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->mediaLibraryService->delete($media);

        return redirect()
            ->route('dashboard.media.index')
            ->with('status', 'Media deleted.');
    }
}
