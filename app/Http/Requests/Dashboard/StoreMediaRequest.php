<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreMediaRequest extends FormRequest
{
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    private const MAX_VIDEO_BYTES = 100 * 1024 * 1024;

    /**
     * Route-level ['auth', 'admin'] middleware already gates every
     * dashboard route, so authorization here is intentionally a pass-through.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpeg,png,webp,mp4,webm',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $isVideo = str_starts_with((string) $value->getMimeType(), 'video/');
                    $max = $isVideo ? self::MAX_VIDEO_BYTES : self::MAX_IMAGE_BYTES;

                    if ($value->getSize() > $max) {
                        $fail($isVideo
                            ? 'Videos must not be larger than 100MB.'
                            : 'Images must not be larger than 5MB.');
                    }
                },
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
