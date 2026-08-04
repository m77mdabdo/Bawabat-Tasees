<?php

namespace App\Services\Cms;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ContentPublishingService
{
    /**
     * Store an uploaded image under storage/app/public/{directory} with a
     * randomly generated filename and return its relative disk path.
     */
    public function storeImage(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * Store a new image and remove the previous one, if any. Used when an
     * update replaces an existing cover/flag/avatar image.
     */
    public function replaceImage(UploadedFile $file, string $directory, ?string $previousPath): string
    {
        $this->deleteImage($previousPath);

        return $this->storeImage($file, $directory);
    }

    public function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
