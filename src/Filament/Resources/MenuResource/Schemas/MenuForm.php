<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Schemas;

use Bambamboole\FilamentMenu\FilamentMenu;
use Bambamboole\FilamentMenu\Models\Menu;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        $locations = app(FilamentMenu::class)->getLocations();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament-menu::menu.form.name'))
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
                    ->label(__('filament-menu::menu.form.slug'))
                    ->required()
                    ->maxLength(255)
                    ->unique(Menu::class, 'slug', ignoreRecord: true)
                    ->alphaDash(),

                Select::make('location')
                    ->label(__('filament-menu::menu.edit.location.label'))
                    ->options($locations)
                    ->placeholder(__('filament-menu::menu.edit.location.placeholder'))
                    ->unique(Menu::class, 'location', ignoreRecord: true)
                    ->visible(fn (): bool => $locations !== []),
            ]);
    }
}
