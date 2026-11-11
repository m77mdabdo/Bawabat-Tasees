<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->getRecord()->roles->first()?->name;

        return $data;
    }

    /** Role and status are written here, never by mass assignment, and never for a protected account. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $record->fill(['name' => $data['name'], 'email' => $data['email'], 'locale' => $data['locale']]);

        if (! UserResource::isProtected($record)) {
            if (array_key_exists('is_active', $data)) {
                $record->is_active = (bool) $data['is_active'];
            }
            if (! empty($data['role'])) {
                $record->syncRoles([$data['role']]);
            }
        }

        $record->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(fn () => ! UserResource::isProtected($this->getRecord()))];
    }
}
