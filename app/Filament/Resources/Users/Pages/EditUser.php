<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasFixedCancelAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Swis\Filament\Activitylog\Actions\ActivitylogAction;

class EditUser extends EditRecord
{
    use HasFixedCancelAction;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ActivitylogAction::make()
        ];
    }
}
