<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages;

use Bambamboole\FilamentMenu\Filament\Resources\MenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
