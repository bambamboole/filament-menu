<?php

namespace Bambamboole\FilamentMenu\Database\Factories;

use Bambamboole\FilamentMenu\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Menu> */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    /** @return array{name: string, slug: string} */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
        ];
    }
}
