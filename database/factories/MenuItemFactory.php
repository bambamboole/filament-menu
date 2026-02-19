<?php

namespace Bambamboole\FilamentMenu\Database\Factories;

use Bambamboole\FilamentMenu\Models\Menu;
use Bambamboole\FilamentMenu\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MenuItem> */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    /** @return array{menu_id: int, label: string, url: string, target: string, type: string, sort_order: int} */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'label' => fake()->words(2, true),
            'url' => fake()->url(),
            'target' => '_self',
            'type' => 'link',
            'sort_order' => 0,
        ];
    }
}
