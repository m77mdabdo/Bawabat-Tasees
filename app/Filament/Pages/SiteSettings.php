<?php

namespace App\Filament\Pages;

use App\Filament\Support\Bilingual;
use App\Filament\Support\Fields;
use App\Services\Cms\SettingsRepository;
use App\Services\Cms\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Global site details (name, contact, social links, SEO defaults, visitor tracking).
 * The form is built from SettingsService::DEFINITION, so a new setting is one line there.
 *
 * @property-read Schema $form
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.form-page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('settings.manage');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.settings.title');
    }

    public function getTitle(): string
    {
        return __('dashboard.settings.title');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.settings.subtitle');
    }

    public function mount(): void
    {
        $settings = app(SettingsRepository::class);
        $values = [];
        foreach (SettingsService::keys() as $key => $type) {
            $value = $settings->get($key);
            $values[$key] = $type === SettingsService::TYPE_TRANSLATED ? (is_array($value) ? $value : ['ar' => $value]) : $value;
        }
        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];
        foreach (SettingsService::DEFINITION as $group => $keys) {
            $fields = [];
            foreach ($keys as $key => $type) {
                $label = __("dashboard.settings.fields.$key");
                $fields[] = match ($type) {
                    SettingsService::TYPE_TRANSLATED => Bilingual::tabs(fn (string $l) => [
                        TextInput::make("$key.$l")->label($label)->maxLength(255)->extraInputAttributes(Bilingual::dir($l)),
                    ], $key),
                    SettingsService::TYPE_IMAGE => Fields::image($key, 'settings', $label)->helperText(__('dashboard.settings.logo_hint')),
                    SettingsService::TYPE_EMAIL => TextInput::make($key)->label($label)->email()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
                    SettingsService::TYPE_PHONE => TextInput::make($key)->label($label)->tel()->regex('/^[0-9+\-\s()]{6,30}$/')->extraInputAttributes(['dir' => 'ltr']),
                    SettingsService::TYPE_URL => TextInput::make($key)->label($label)->url()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
                    SettingsService::TYPE_BOOLEAN => Toggle::make($key)->label($label),
                    SettingsService::TYPE_INTEGER => TextInput::make($key)->label($label)->integer()->minValue(1)->maxValue(3650),
                    default => TextInput::make($key)->label($label)->maxLength(500),
                };
            }
            $sections[] = Section::make(__("dashboard.settings.groups.$group"))
                ->description(__("dashboard.settings.group_hints.$group"))
                ->columns(2)->collapsible()->schema($fields);
        }

        return $schema->statePath('data')->components($sections);
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label(__('dashboard.settings.save'))->submit('save')];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $service = app(SettingsService::class);

        $logo = $data['site_logo'] ?? null;
        unset($data['site_logo']);
        $service->update($data);
        if (is_string($logo) && $logo !== '') {
            $service->replaceLogo($logo);
        }

        Notification::make()->success()->title(__('dashboard.flash.settings_updated'))->send();
    }
}
