<?php

namespace App\Filament\Resources\Users;

use App\Auth\PermissionMatrix;
use App\Enums\Role;
use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Mail\StaffAccessLink;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Throwable;

/**
 * Staff accounts (Super Admin only). Nobody types a password for someone else: new staff get an
 * emailed invitation to choose their own. The last active Super Admin cannot be removed, demoted
 * or switched off, and nobody can do that to themselves, so the dashboard can never be locked out.
 */
class UserResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = User::class;

    protected static string $viewPermission = 'users.manage';

    protected static string $managePermission = 'users.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Staff member');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Staff and roles');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    public static function isLastSuperAdmin(User $user): bool
    {
        return $user->isSuperAdmin() && $user->is_active
            && User::role(Role::SuperAdmin->value)->where('is_active', true)->count() <= 1;
    }

    /** True if changing this account's role or status could lock someone out. */
    public static function isProtected(?User $record): bool
    {
        return $record && (auth()->user()?->is($record) || self::isLastSuperAdmin($record));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Account'))->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label(__('Name'))->required()->maxLength(120),
                TextInput::make('email')->label(__('Email'))->required()->email()->maxLength(190)
                    ->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
                Select::make('role')->label(__('Role'))->required()->native(false)->options(fn () => Role::options())
                    ->disabled(fn (?User $record) => self::isProtected($record))
                    ->dehydrated(fn (?User $record) => ! self::isProtected($record))
                    ->helperText(fn (?User $record) => self::isProtected($record)
                        ? __('You cannot change your own role, or that of the only Super Admin.')
                        : __('“Roles and permissions” on the list page shows what each role can do.')),
                Select::make('locale')->label(__('Dashboard language'))->required()->native(false)->default('ar')
                    ->options(['ar' => 'العربية', 'en' => 'English']),
                Toggle::make('is_active')->label(__('Can sign in'))->default(true)
                    ->disabled(fn (?User $record) => self::isProtected($record))
                    ->dehydrated(fn (?User $record) => ! self::isProtected($record))
                    ->helperText(__('Switch off to block access while keeping the account and its history.')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable()->weight('semibold')
                    ->description(fn (User $u) => $u->email),
                TextColumn::make('role')->label(__('Role'))->badge()
                    ->state(fn (User $u) => $u->roles->map(fn ($r) => __(Role::tryFrom($r->name)?->label() ?? $r->name))->all()),
                IconColumn::make('is_active')->label(__('Can sign in'))->boolean(),
                TextColumn::make('last_login_at')->label(__('Last sign-in'))->since()->sortable()->placeholder(__('Never')),
            ])
            ->filters([
                SelectFilter::make('role')->label(__('Role'))->options(fn () => Role::options())
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $v) => $q->role($v))),
                TernaryFilter::make('is_active')->label(__('Can sign in')),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('sendLink')->label(__('Send password link'))->icon('heroicon-o-envelope')->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription(fn (User $r) => __('Emails :email a link to choose a new password.', ['email' => $r->email]))
                        ->action(fn (User $record) => self::notifyResult(self::sendAccessLink($record, invitation: false), $record)),
                    DeleteAction::make()->visible(fn (User $r) => ! self::isProtected($r))
                        ->before(fn (User $record) => abort_if(self::isProtected($record), 403)),
                ]),
            ]);
    }

    /** Emails a time-limited link to the dashboard's own "choose a password" page. */
    public static function sendAccessLink(User $user, bool $invitation): bool
    {
        try {
            $url = Filament::getPanel('dashboard')->getResetPasswordUrl(Password::broker()->createToken($user), $user);
            Mail::to($user->email, $user->name)->send(new StaffAccessLink($user->loadMissing('roles'), $url, $invitation));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public static function notifyResult(bool $sent, User $user): void
    {
        $sent
            ? Notification::make()->success()->title(__('Email sent to :email', ['email' => $user->email]))->send()
            : Notification::make()->danger()->title(__('Sending failed'))->body(__('Check the mail settings, then use “Send password link”.'))->send();
    }

    /** Role × permission table for the "Roles and permissions" window. @return array<string, mixed> */
    public static function matrix(): array
    {
        return [
            'roles' => Role::cases(),
            'permissions' => collect(PermissionMatrix::all())->map(fn (array $roles) => array_map(fn (Role $r) => $r->value, $roles))->all(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
