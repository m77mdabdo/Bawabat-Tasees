<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Testimonials\TestimonialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTestimonial extends EditRecord
{
    use EditsTranslations;

    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
