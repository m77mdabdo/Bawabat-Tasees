<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Models\Media;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageMedia extends ManageRecords
{
    protected static string $resource = MediaResource::class;

    /** Images up to 2 MB and videos up to 20 MB; raster formats only (no SVG). */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')->label(__('Upload'))->icon('heroicon-o-arrow-up-tray')
                ->visible(fn () => MediaResource::canCreate())
                ->schema([
                    FileUpload::make('file')->label(__('File'))->required()->disk('public')->directory('media')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm'])
                        ->maxSize(20 * 1024),
                    TextInput::make('alt_text')->label(__('Description (alt text)'))->maxLength(255),
                ])
                ->action(function (array $data) {
                    $disk = Storage::disk('public');
                    $mime = (string) $disk->mimeType($data['file']);
                    if (str_starts_with($mime, 'image/') && $disk->size($data['file']) > 2 * 1024 * 1024) {
                        $disk->delete($data['file']);
                        Notification::make()->danger()->title(__('Images must be 2 MB or smaller.'))->send();

                        return;
                    }
                    Media::create([
                        'path' => $data['file'], 'disk' => 'public', 'mime_type' => $mime, 'size' => $disk->size($data['file']),
                        'type' => str_starts_with($mime, 'video/') ? 'video' : 'image',
                        'alt_text' => $data['alt_text'] ?? null, 'uploaded_by' => auth()->id(),
                    ]);
                    Notification::make()->success()->title(__('Uploaded'))->send();
                }),
        ];
    }
}
