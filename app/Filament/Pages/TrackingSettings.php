<?php

namespace App\Filament\Pages;

use App\Models\TrackingSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Marketing pixel IDs (Meta, GTM, GA4, Google Ads, TikTok). Each must match its format
 * (TrackingSetting::FORMATS) and nothing loads on the site until it is switched on,
 * and then only after the visitor accepts cookies.
 *
 * @property-read Schema $form
 */
class TrackingSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.form-page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** Super Admins only: a GTM container can run any script on the public site (PermissionMatrix). */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('tracking.manage');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.tracking_settings.heading');
    }

    public function getTitle(): string
    {
        return __('dashboard.tracking_settings.heading');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.tracking_settings.intro');
    }

    public function mount(): void
    {
        $this->form->fill(TrackingSetting::all()->mapWithKeys(fn (TrackingSetting $s) => [
            $s->key => ['value' => $s->value, 'is_active' => $s->is_active],
        ])->all());
    }

    public function form(Schema $schema): Schema
    {
        $rows = [];
        foreach (TrackingSetting::FORMATS as $key => $pattern) {
            $rows[] = Grid::make(['default' => 1, 'sm' => 3])->schema([
                TextInput::make("$key.value")->label(__("dashboard.tracking_settings.$key"))->columnSpan(['default' => 1, 'sm' => 2])
                    ->maxLength(255)->regex($pattern)->helperText(__("dashboard.tracking_settings.format_hint.$key"))
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->required(fn (Get $get) => (bool) $get("$key.is_active"))
                    ->validationMessages(['required' => __('dashboard.tracking_settings.validation.required_when_active')]),
                Toggle::make("$key.is_active")->label(__('dashboard.tracking_settings.active'))->inline(false)->live(),
            ]);
        }

        return $schema->statePath('data')->components([Section::make()->schema($rows)]);
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label(__('dashboard.tracking_settings.save'))->submit('save')];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach (TrackingSetting::all() as $setting) {
            $row = $data[$setting->key] ?? [];
            $setting->update(['value' => filled($row['value'] ?? null) ? trim($row['value']) : null, 'is_active' => (bool) ($row['is_active'] ?? false)]);
        }

        Notification::make()->success()->title(__('dashboard.flash.tracking_settings_updated'))->send();
    }
}
