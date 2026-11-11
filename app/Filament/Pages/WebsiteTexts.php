<?php

namespace App\Filament\Pages;

use App\Content\SiteTexts;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Every fixed text on the public website (headings, buttons, form labels, footer, error pages),
 * in Arabic and English, grouped by where it appears. Backed by App\Content\SiteTexts:
 * clearing a field brings back the original wording.
 *
 * @property-read Schema $form
 */
class WebsiteTexts extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.form-page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** Section titles, in the order the site shows them. */
    private const SECTIONS = [
        'home' => 'Homepage', 'nav' => 'Header and menu', 'footer' => 'Footer', 'brand' => 'Site name',
        'about' => 'About us page', 'services' => 'Services pages', 'countries' => 'Countries page', 'faqs' => 'FAQ page',
        'articles' => 'Blog', 'consultation' => 'Consultation form', 'contact' => 'Contact form', 'common' => 'Common words',
        'flash' => 'Messages after sending a form', 'consent' => 'Cookie banner', 'motion' => 'Video and slideshow controls',
        'errors' => 'Error pages',
    ];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('content.manage');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getNavigationLabel(): string
    {
        return __('Website texts');
    }

    public function getTitle(): string
    {
        return __('Website texts');
    }

    public function getSubheading(): ?string
    {
        return __('Every fixed text on the site, in Arabic and English. Clear a field to bring back the original wording.');
    }

    /** Form state keys cannot contain dots, so "home.hero_heading" is stored as "home__hero_heading". */
    private static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    public function mount(): void
    {
        $texts = app(SiteTexts::class);
        $state = [];
        foreach ($texts->sections() as $keys) {
            foreach (array_keys($keys) as $key) {
                foreach (SiteTexts::LOCALES as $locale) {
                    $state[self::field($key)][$locale] = $texts->current($key, $locale);
                }
            }
        }
        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $texts = app(SiteTexts::class);
        $sections = [];
        $order = array_flip(array_keys(self::SECTIONS));
        $all = $texts->sections();
        uksort($all, fn ($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

        foreach ($all as $section => $keys) {
            $rows = [];
            foreach (array_keys($keys) as $key) {
                // Each text is titled by its original wording in the dashboard language, so editors recognise it.
                $title = Str::limit((string) $texts->default($key, app()->getLocale() === 'en' ? 'en' : 'ar'), 70);
                $rows[] = Fieldset::make($title)->columns(['default' => 1, 'md' => 2])->schema(array_map(
                    fn (string $locale) => $this->input($key, $locale, (string) $texts->default($key, $locale)),
                    SiteTexts::LOCALES,
                ));
            }
            $sections[] = Section::make(__(self::SECTIONS[$section] ?? Str::headline($section)))
                ->description(trans_choice(':count text|:count texts', count($keys)))
                ->collapsible()->collapsed($section !== 'home')
                ->schema($rows);
        }

        return $schema->statePath('data')->components($sections);
    }

    private function input(string $key, string $locale, string $default): TextInput|Textarea
    {
        $name = self::field($key).'.'.$locale;
        $field = mb_strlen($default) > 90 ? Textarea::make($name)->rows(2) : TextInput::make($name)->maxLength(500);
        $placeholders = SiteTexts::placeholders($default);

        return $field
            ->label($locale === 'ar' ? 'العربية' : 'English')
            ->extraInputAttributes(['dir' => $locale === 'ar' ? 'rtl' : 'ltr'])
            ->placeholder($default)
            ->helperText($placeholders ? __('Keep :list in the text: the site fills them in.', ['list' => implode(' ', $placeholders)]) : null)
            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) use ($placeholders) {
                foreach ($placeholders as $p) {
                    if (filled($value) && ! str_contains((string) $value, $p)) {
                        $fail(__('The text must keep :placeholder.', ['placeholder' => $p]));
                    }
                }
            });
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label(__('Save texts'))->submit('save')];
    }

    public function save(): void
    {
        $values = [];
        foreach ($this->form->getState() as $field => $byLocale) {
            $values[str_replace('__', '.', $field)] = (array) $byLocale;
        }
        app(SiteTexts::class)->save($values);

        Notification::make()->success()->title(__('Website texts saved'))->send();
    }
}
