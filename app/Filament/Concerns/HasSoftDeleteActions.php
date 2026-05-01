<?php

namespace App\Filament\Concerns;

use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait HasSoftDeleteActions
{
    //NOTE:: use in Resource
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // NOTE:: edit page
    protected static function getSoftDeleteActions(): array
    {
        return [
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    // NOTE:: bulk table
    protected static function getSoftDeleteBulkActions(): array
    {
        return [
            ForceDeleteBulkAction::make(),
            RestoreBulkAction::make(),
        ];
    }

    //
    protected static function getSoftDeleteFilters(): array
    {
        return [
            TrashedFilter::make(),
        ];
    }
}
