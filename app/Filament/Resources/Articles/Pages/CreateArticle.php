<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    use EditsTranslations;

    protected static string $resource = ArticleResource::class;
}
