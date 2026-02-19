<?php

namespace Bambamboole\FilamentMenu;

use Bambamboole\FilamentMenu\Models\Menu;

class FilamentMenu
{
    public function getByLocation(string $location): ?Menu
    {
        return Menu::query()
            ->where('location', $location)
            ->with('items')
            ->first();
    }

    public function getBySlug(string $slug): ?Menu
    {
        return Menu::query()
            ->where('slug', $slug)
            ->with('items')
            ->first();
    }
}
