<?php

namespace App\Services\Cms;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Loads the whole settings table once and answers every lookup from
 * memory.
 *
 * Before this, each caller ran its own `where('key', ?)->value('value')`.
 * The homepage issued 8 such queries per render and /contact 7, for a
 * table of 10 rows — every public page paid at least 3 (the navbar
 * composer plus the two SEO defaults).
 *
 * Registered as a singleton in AppServiceProvider, so the load happens at
 * most once per request and only if something actually asks for a
 * setting. Deliberately NOT cached across requests: an admin editing a
 * setting must see it immediately, and one query for 10 rows is already
 * cheap. Add a cache layer here later if the table ever grows.
 */
class SettingsRepository
{
    /** @var Collection<string, mixed>|null */
    private ?Collection $settings = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key, $default);
    }

    /**
     * A non-empty string, or null. Most callers render these directly, and
     * a blank string is not a usable phone number or address.
     */
    public function string(string $key): ?string
    {
        $value = $this->get($key);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * A stored flag.
     *
     * Tolerant of shape on purpose: this column has held JSON-encoded
     * scalars since it was created, so a flag can legitimately arrive as
     * true, "1", 1 or "true" depending on whether it came from the seeder,
     * the editor form or a hand-edited row. FILTER_VALIDATE_BOOLEAN treats
     * all of those alike, and null (never set) falls back to $default rather
     * than to false — the difference matters for a feature whose default is
     * "on".
     */
    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * A stored whole number, or $default when unset or not numeric. Callers
     * that use the result in a query are expected to clamp it themselves —
     * see VisitorTrackingGate::retentionDays().
     */
    public function integer(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * A bilingual setting in the current locale, falling back to Arabic —
     * the same fallback rule HasTranslations applies to model fields, so
     * a setting with no English value shows its Arabic one rather than
     * rendering blank.
     *
     * Tolerates a plain string too, so a value that has not yet been
     * converted to the {ar, en} shape still renders.
     */
    public function translated(string $key, ?string $locale = null): ?string
    {
        $value = $this->get($key);

        if (is_string($value)) {
            return trim($value) !== '' ? $value : null;
        }

        if (! is_array($value)) {
            return null;
        }

        $locale ??= app()->getLocale();
        $fallback = config('app.fallback_locale', 'ar');

        foreach ([$locale, $fallback] as $candidate) {
            $candidateValue = $value[$candidate] ?? null;

            if (is_string($candidateValue) && trim($candidateValue) !== '') {
                return $candidateValue;
            }
        }

        return null;
    }

    /**
     * @return Collection<string, mixed>
     */
    public function all(): Collection
    {
        return $this->settings ??= Setting::query()
            ->get(['key', 'value'])
            ->pluck('value', 'key');
    }

    /**
     * Drops the in-memory copy so a write is reflected by later reads in
     * the same request.
     */
    public function flush(): void
    {
        $this->settings = null;
    }
}
