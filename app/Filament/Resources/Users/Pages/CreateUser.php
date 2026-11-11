<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/** Creates the account with an unusable random password and emails an invitation to choose one. */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): User
    {
        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'locale' => $data['locale'] ?? 'ar']);
        $user->password = Str::password(40);
        $user->is_active = (bool) ($data['is_active'] ?? true);
        $user->email_verified_at = now();
        $user->save();
        $user->syncRoles([$data['role']]);

        return $user;
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->getRecord();
        UserResource::notifyResult(UserResource::sendAccessLink($user, invitation: true), $user);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // afterCreate reports whether the invitation went out.
    }
}
