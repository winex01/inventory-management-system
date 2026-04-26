<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasFixedCancelAction;
use App\Filament\Concerns\HasSoftDeleteActions;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Swis\Filament\Activitylog\Actions\ActivitylogAction;

class EditUser extends EditRecord
{
    use HasFixedCancelAction;
    use HasSoftDeleteActions;

    public array $oldRoles = [];

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ...static::getSoftDeleteActions(),
            ActivitylogAction::make()
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord()->fresh();
        $oldRoles = collect($this->oldRoles ?? []);
        $newRoles = $record->roles->pluck('name');

        $attached = $newRoles->diff($oldRoles);
        $detached = $oldRoles->diff($newRoles);

        if ($detached->isNotEmpty() && auth()->check()) {
            activity()
                ->performedOn($record)
                ->causedBy(auth()->user())
                ->event('detached')
                ->withProperties(['attributes' => ['roles' => $detached->implode(', ')]])
                ->log('role detached');
        }

        if ($attached->isNotEmpty() && auth()->check()) {
            activity()
                ->performedOn($record)
                ->causedBy(auth()->user())
                ->event('attached')
                ->withProperties(['attributes' => ['roles' => $attached->implode(', ')]])
                ->log('role attached');
        }
    }
}
