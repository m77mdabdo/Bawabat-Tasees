<?php

namespace App\Services\Cms;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaLibraryService
{
    /**
     * Store an uploaded image or video in the standalone media library and
     * create its corresponding Media row. Unlike ContentPublishingService
     * (which stores a path string on a parent model's own column), this
     * creates an independent, listable Media record.
     */
    public function upload(UploadedFile $file, ?string $altText, ?User $uploadedBy): Media
    {
        $path = $file->store('media', 'public');

        return Media::create([
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image',
            'alt_text' => $altText,
            'uploaded_by' => $uploadedBy?->id,
        ]);
    }

    /**
     * Delete both the disk file and the database row so nothing is
     * orphaned on either side.
     */
    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->path);

        $media->delete();
    }
}
