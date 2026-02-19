<?php

namespace Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Schemas;

use Bambamboole\FilamentMenu\Models\Menu;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, ?Menu $record): void {
                        if ($record !== null || $state === null) {
                            return;
                        }

                        $set('slug', Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Menu::class, 'slug', ignoreRecord: true)
                    ->alphaDash(),
            ]);
    }
}
