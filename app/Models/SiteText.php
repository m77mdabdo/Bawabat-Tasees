<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One edited website text (see App\Content\SiteTexts). */
class SiteText extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
