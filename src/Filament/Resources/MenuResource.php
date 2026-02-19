<?php

namespace Bambamboole\FilamentMenu\Filament\Resources;

use BackedEnum;
use Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages\EditMenu;
use Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages\ListMenus;
use Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Schemas\MenuForm;
use Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Tables\MenusTable;
use Bambamboole\FilamentMenu\Models\Menu;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBars3;

    protected static UnitEnum | string | null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return MenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenusTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
