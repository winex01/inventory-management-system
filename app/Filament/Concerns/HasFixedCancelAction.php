<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Actions\Action;

trait HasFixedCancelAction
{
    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
            ->color('gray')
            ->url($this->getResourceUrl());
    }
}
