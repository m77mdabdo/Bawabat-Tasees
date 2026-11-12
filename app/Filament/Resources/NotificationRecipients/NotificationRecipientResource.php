<?php

namespace App\Filament\Resources\NotificationRecipients;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\NotificationRecipients\Pages\ManageNotificationRecipients;
use App\Mail\TestMail;
use App\Models\NotificationRecipient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** The email addresses told instantly about every new enquiry (App\Listeners\EmailTeamAboutLead). */
class NotificationRecipientResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = NotificationRecipient::class;

    protected static string $viewPermission = 'recipients.manage';

    protected static string $managePermission = 'recipients.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Enquiries');
    }

    public static function getModelLabel(): string
    {
        return __('Alert recipient');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Enquiry email alerts');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label(__('Name'))->maxLength(120),
            TextInput::make('email')->label(__('Email'))->required()->email()->maxLength(190)
                ->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
            Toggle::make('receives_consultations')->label(__('Consultation requests'))->default(true),
            Toggle::make('receives_contact_messages')->label(__('Contact messages'))->default(true),
            Toggle::make('is_active')->label(__('Active'))->default(true)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Nobody receives enquiry emails yet'))
            ->emptyStateDescription(__('Until you add someone, alerts go to the contact email in Site settings.'))
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->placeholder('—'),
                TextColumn::make('email')->label(__('Email'))->searchable()->copyable(),
                ToggleColumn::make('receives_consultations')->label(__('Consultations')),
                ToggleColumn::make('receives_contact_messages')->label(__('Contact')),
                ToggleColumn::make('is_active')->label(__('Active')),
                TextColumn::make('last_notified_at')->label(__('Last alert'))->since()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('test')->label(__('Send test'))->icon('heroicon-o-paper-airplane')->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(fn (NotificationRecipient $r) => __('Sends a test email to :email.', ['email' => $r->email]))
                    ->action(function (NotificationRecipient $record) {
                        try {
                            Mail::to($record->email, $record->name)->send(new TestMail(config('notifications.staff_locale', 'ar')));
                            Notification::make()->success()->title(__('Test email sent to :email', ['email' => e($record->email)]))->send();
                        } catch (Throwable $e) {
                            report($e);
                            Notification::make()->danger()->title(__('Sending failed'))->body(e($e->getMessage()))->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageNotificationRecipients::route('/')];
    }
}
