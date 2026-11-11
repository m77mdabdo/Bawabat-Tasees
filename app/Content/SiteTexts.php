<?php

namespace App\Content;

use App\Models\SiteText;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * The fixed website texts (buttons, headings, form labels...) from lang/{ar,en}/site.php,
 * with any edits made in the dashboard on top.
 *
 * The language files stay the defaults; only texts that differ are stored (site_texts),
 * so clearing a field in the dashboard restores the default. Read once per request from
 * the cache and applied by App\Translation\OverridingLoader to every __('site.…') call.
 */
final class SiteTexts
{
    public const GROUP = 'site';

    public const LOCALES = ['ar', 'en'];

    private const CACHE_KEY = 'site_texts.overrides';

    /** @var array<string, array<string, string>>|null */
    private ?array $overrides = null;

    /**
     * Texts shown in the dashboard, by section. "errors" holds the error pages; "consent",
     * "motion" and "flash" are short system messages, also editable.
     *
     * @return array<string, array<string, string>> section => [dot key => Arabic default]
     */
    public function sections(): array
    {
        $sections = [];
        foreach ($this->defaults('ar') as $section => $lines) {
            $sections[$section] = is_array($lines)
                ? Arr::mapWithKeys(Arr::dot($lines), fn ($v, $k) => ["{$section}.{$k}" => (string) $v])
                : [$section => (string) $lines];
        }

        return $sections;
    }

    /** @return array<string, mixed> the language-file texts, untouched by edits */
    public function defaults(string $locale): array
    {
        $file = lang_path("{$locale}/".self::GROUP.'.php');

        return is_file($file) ? require $file : [];
    }

    public function default(string $key, string $locale): ?string
    {
        $value = Arr::get($this->defaults($locale), $key);

        return is_string($value) ? $value : null;
    }

    /** What the site shows now for a key, in one language. */
    public function current(string $key, string $locale): ?string
    {
        return $this->overrides()[$key][$locale] ?? $this->default($key, $locale);
    }

    /** @return array<string, array<string, string>> key => [locale => text] */
    public function overrides(): array
    {
        if ($this->overrides !== null) {
            return $this->overrides;
        }

        try {
            return $this->overrides = Cache::rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('site_texts')
                ? SiteText::query()->pluck('value', 'key')->map(fn ($v) => array_filter((array) $v, 'is_string'))->all()
                : []);
        } catch (Throwable) {
            return $this->overrides = []; // no database yet (install, some console commands): defaults only
        }
    }

    /**
     * Saves the submitted texts. A text equal to its default, or left empty, is not stored.
     *
     * @param  array<string, array<string, ?string>>  $values  key => [locale => text]
     */
    public function save(array $values): void
    {
        foreach ($values as $key => $byLocale) {
            if ($this->default($key, 'ar') === null) {
                continue; // not a real text key
            }

            $changed = [];
            foreach (self::LOCALES as $locale) {
                $text = trim((string) ($byLocale[$locale] ?? ''));
                if ($text !== '' && $text !== $this->default($key, $locale)) {
                    $changed[$locale] = $text;
                }
            }

            $changed
                ? SiteText::query()->updateOrCreate(['key' => $key], ['value' => $changed])
                : SiteText::query()->where('key', $key)->delete();
        }

        $this->flush();
    }

    public function reset(string $key): void
    {
        SiteText::query()->where('key', $key)->delete();
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->overrides = null;
        app('translator')->setLoaded([]);
    }

    /** The ":name" style placeholders a text must keep for the site to fill it in. @return list<string> */
    public static function placeholders(string $text): array
    {
        preg_match_all('/:[a-z_]+/i', $text, $m);

        return array_values(array_unique($m[0]));
    }
}
