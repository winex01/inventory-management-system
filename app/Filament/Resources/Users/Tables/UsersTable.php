<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Concerns\HasSoftDeleteActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    use HasSoftDeleteActions;

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutSuperAdmin())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault : false),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault : false),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                ...static::getSoftDeleteFilters(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),

                ...static::getSoftDeleteActions(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),

                ...static::getSoftDeleteBulkActions(),
            ]);
    }
}
