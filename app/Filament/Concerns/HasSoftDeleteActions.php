<?php

namespace App\Filament\Concerns;

use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait HasSoftDeleteActions
{
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function getSoftDeleteActions(): array
    {
        return [
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected static function getSoftDeleteBulkActions(): array
    {
        return [
            ForceDeleteBulkAction::make(),
            RestoreBulkAction::make(),
        ];
    }

    protected static function getSoftDeleteFilters(): array
    {
        return [
            TrashedFilter::make(),
        ];
    }
}
