<?php

namespace App\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use Illuminate\View\View;

class PublicLayout extends Component
{
    /**
     * @param  $title  Page title. Still the source for <title> on list
     *                pages; on detail pages a SeoMeta meta_title outranks it.
     * @param  $seoModel  The record backing this page (Article/Page/Country/
     *                   Service). Its seoMeta relation supplies the tag
     *                   values — list pages leave this null.
     * @param  $seoDescription  Explicit meta description for pages with no
     *                         backing record.
     * @param  $seoType  Open Graph type override; defaults to "article" for
     *                  Articles and "website" for everything else.
     */
    public function __construct(
        public ?string $title = null,
        public ?Model $seoModel = null,
        public ?string $seoDescription = null,
        public ?string $seoType = null,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.public');
    }
}
