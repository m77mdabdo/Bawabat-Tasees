<?php

namespace App\Translation;

use App\Content\SiteTexts;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Arr;

/**
 * Wraps Laravel's file loader: when the "site" group is loaded, texts edited in the dashboard
 * replace the language-file ones. Every other group is passed through unchanged.
 */
final class OverridingLoader implements Loader
{
    public function __construct(private readonly Loader $files) {}

    public function load($locale, $group, $namespace = null)
    {
        $lines = $this->files->load($locale, $group, $namespace);

        if ($group !== SiteTexts::GROUP || ($namespace !== null && $namespace !== '*')) {
            return $lines;
        }

        foreach (app(SiteTexts::class)->overrides() as $key => $byLocale) {
            if (isset($byLocale[$locale]) && Arr::has($lines, $key)) {
                Arr::set($lines, $key, $byLocale[$locale]);
            }
        }

        return $lines;
    }

    public function addNamespace($namespace, $hint)
    {
        $this->files->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path)
    {
        $this->files->addJsonPath($path);
    }

    public function namespaces()
    {
        return $this->files->namespaces();
    }
}
