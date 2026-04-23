<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required(),

                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(
                                table: 'users',
                                column: 'email',
                                ignorable: fn ($record) => $record,
                            ),

                        // Using CheckboxList Component
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->columns(2)
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => str($record->name)->replace('_', ' ')->title())
                            ->afterStateHydrated(function ($component, $record) {
                                // Capture old roles BEFORE any changes when form loads
                                if ($record) {
                                    $oldRoles = $record->roles->pluck('name')->implode(', ');
                                    cache()->put('old_user_roles_' . $record->id, $oldRoles, now()->addMinutes(5));
                                }
                            })
                            ->afterStateUpdated(function ($state, $record) {
                                // Capture after change and log
                                $oldRoles = cache()->pull('old_user_roles_' . $record->id);

                                // Get the new role names from the selected IDs
                                $newRoles = collect($state)
                                    ->map(fn($roleId) => \Spatie\Permission\Models\Role::find($roleId)?->name)
                                    ->filter()
                                    ->implode(', ');

                                activity()
                                    ->performedOn($record)
                                    ->causedBy(auth()->user())
                                    ->event('updated')  // Change from 'commented' to 'updated'
                                    ->withProperties([
                                        'old' => [
                                            'role' => $oldRoles,
                                        ],
                                        'attributes' => [
                                            'role' => $newRoles,
                                        ]
                                    ])
                                    ->log('updated');
                            }),
                    ])
                    ->columns(2) // This creates the 2-column layout
                    ->columnSpanFull()
            ]);
    }
}
