<?php

namespace Bambamboole\FilamentMenu;

use Bambamboole\FilamentMenu\Models\Menu;
use Bambamboole\FilamentMenu\Models\MenuLocation;

class FilamentMenu
{
    public function getByLocation(string $location): ?Menu
    {
        $menuLocation = MenuLocation::query()
            ->where('location', $location)
            ->first();

        if (! $menuLocation) {
            return null;
        }

        return $menuLocation->menu->load('items');
    }

    public function getBySlug(string $slug): ?Menu
    {
        return Menu::query()
            ->where('slug', $slug)
            ->with('items')
            ->first();
    }
}
