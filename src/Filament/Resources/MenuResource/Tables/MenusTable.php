<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Tables;

use Bambamboole\FilamentMenu\FilamentMenu;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        $service = app(FilamentMenu::class);
        $locales = $service->getLocales();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('slug'),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label(__('filament-menu::menu.table.columns.items')),

                TextColumn::make('location')
                    ->badge()
                    ->label(__('filament-menu::menu.table.columns.location')),

                TextColumn::make('locale')
                    ->badge()
                    ->label(__('filament-menu::menu.table.columns.locale'))
                    ->visible(fn (): bool => $locales !== []),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('locale')
                    ->label(__('filament-menu::menu.table.columns.locale'))
                    ->options($locales)
                    ->visible(fn (): bool => $locales !== []),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
