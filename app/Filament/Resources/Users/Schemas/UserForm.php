<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),

                // Using CheckboxList Component
                CheckboxList::make('roles')
                    ->relationship('roles', 'name')
                    ->columns(2)
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn ($record) => str($record->name)->replace('_', ' ')->title()),

            ]);
    }
}
