<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/** Small field presets used across resources, so the same thing looks and validates the same everywhere. */
final class Fields
{
    /** A raster image on the public disk. No SVG: an SVG is a script-capable document served from this origin. */
    public static function image(string $name, string $directory, ?string $label = null): FileUpload
    {
        return FileUpload::make($name)
            ->label($label ?? __('Image'))
            ->image()
            ->imageEditor()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(2048);
    }

    public static function slug(string $table): TextInput
    {
        return TextInput::make('slug')
            ->label(__('Link (slug)'))
            ->helperText(__('Leave empty to build it from the Arabic title.'))
            ->maxLength(255)
            ->alphaDash()
            ->unique($table, 'slug', ignoreRecord: true)
            ->extraInputAttributes(['dir' => 'ltr']);
    }

    public static function sortOrder(): TextInput
    {
        return TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->default(0);
    }

    public static function active(string $name = 'is_active', ?string $label = null): Toggle
    {
        return Toggle::make($name)->label($label ?? __('Shown on the website'))->default(true);
    }
}
