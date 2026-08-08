<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    use HasFactory, HasTranslations;

    public const LINK_TYPES = ['route', 'url', 'none'];

    public const TARGETS = ['_self', '_blank'];

    /**
     * The ONLY route names a menu item may point at.
     *
     * Every entry is a parameter-free public route. Routes that need a
     * bound model (services.show, articles.show) are deliberately absent:
     * a menu entry pointing at one could not be resolved without a
     * parameter and would 500 the navbar on every page of the site.
     *
     * Keys are route names; values are the translation key used to label
     * them in the dashboard's route picker.
     */
    public const ROUTE_WHITELIST = [
        'home' => 'dashboard.menu.routes.home',
        'services.index' => 'dashboard.menu.routes.services',
        'countries.index' => 'dashboard.menu.routes.countries',
        'faqs.index' => 'dashboard.menu.routes.faqs',
        'articles.index' => 'dashboard.menu.routes.articles',
        'pages.about' => 'dashboard.menu.routes.about',
        'pages.why-invest' => 'dashboard.menu.routes.why_invest',
        'pages.formation-process' => 'dashboard.menu.routes.formation_process',
        'pages.requirements' => 'dashboard.menu.routes.requirements',
        'pages.privacy-policy' => 'dashboard.menu.routes.privacy_policy',
        'pages.terms-and-conditions' => 'dashboard.menu.routes.terms',
        'consultation' => 'dashboard.menu.routes.consultation',
        'contact' => 'dashboard.menu.routes.contact',
    ];

    protected $fillable = [
        'parent_id',
        'label',
        'link_type',
        'route_name',
        'url',
        'target',
        'is_visible',
        'is_system',
        'sort_order',
    ];

    public $translatable = ['label'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    /**
     * Children the public site should render — used by the navbar so a
     * hidden child never leaks into a dropdown.
     */
    public function visibleChildren(): HasMany
    {
        return $this->children()->visible();
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Where this item points, in the CURRENT locale.
     *
     * route → lroute(), so an English visitor gets the /en variant.
     * url   → used verbatim (it may be external, or a root-relative path
     *         the admin typed).
     * none  → null; the caller renders a dropdown trigger, not a link.
     *
     * Returns null rather than throwing if a route name somehow falls
     * outside the whitelist — a bad row must not take the navbar, and
     * therefore every page, down with it.
     */
    public function href(): ?string
    {
        return match ($this->link_type) {
            'url' => $this->url ?: null,
            'route' => $this->resolveRouteHref(),
            default => null,
        };
    }

    private function resolveRouteHref(): ?string
    {
        if (! $this->route_name || ! array_key_exists($this->route_name, self::ROUTE_WHITELIST)) {
            return null;
        }

        return lroute($this->route_name);
    }

    /**
     * True when this item should render as a dropdown rather than a link.
     */
    public function hasVisibleChildren(): bool
    {
        return $this->relationLoaded('visibleChildren')
            ? $this->visibleChildren->isNotEmpty()
            : $this->visibleChildren()->exists();
    }

    /**
     * rel="noopener" is required on target="_blank" links — without it the
     * opened page can reach back through window.opener.
     */
    public function linkRel(): ?string
    {
        return $this->target === '_blank' ? 'noopener' : null;
    }

    /**
     * Whether this item (or one of its children) is the page being viewed,
     * for active-state highlighting.
     */
    public function isActive(): bool
    {
        if ($this->link_type === 'route' && $this->routeMatchesCurrent()) {
            return true;
        }

        // A dropdown parent lights up when one of its children is active.
        if ($this->relationLoaded('visibleChildren')) {
            return $this->visibleChildren->contains(fn (self $child) => $child->isActive());
        }

        return false;
    }

    /**
     * Exact match, plus the one generalisation worth making: an ".index"
     * item also owns its ".show" detail pages, so "خدماتنا" stays
     * highlighted while reading a single service — which is how the
     * hardcoded navbar behaved before it became data-driven.
     *
     * Deliberately NOT a loose prefix match on the first segment: that
     * would light up every pages.* item at once, since About, Why-Invest,
     * Process and Requirements all share the "pages" prefix.
     */
    private function routeMatchesCurrent(): bool
    {
        $current = current_route_base_name();

        if ($current === null || $this->route_name === null) {
            return false;
        }

        if ($this->route_name === $current) {
            return true;
        }

        return str_ends_with($this->route_name, '.index')
            && $current === substr($this->route_name, 0, -6).'.show';
    }
}
