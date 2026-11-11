<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    use EditsTranslations;

    protected static string $resource = FaqResource::class;
}
