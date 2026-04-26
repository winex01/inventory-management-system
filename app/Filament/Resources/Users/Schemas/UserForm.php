<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
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

                        Select::make('roles')
                            ->relationship('roles', 'name', fn ($query) => $query->withoutSuperAdmin())
                            ->preload()
                            ->multiple()
                            ->columns(2)
                            ->searchable()
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    // create property oldRoles so we use it in edit page
                                    $component->getContainer()->getLivewire()->oldRoles = $record->roles->pluck('name')->toArray();
                                }
                            }),
                    ])
                    ->columns(2) // This creates the 2-column layout
                    ->columnSpanFull()
            ]);
    }
}
