<?php

use Bambamboole\FilamentMenu\FilamentMenu;
use Bambamboole\FilamentMenu\Models\Menu;
use Bambamboole\FilamentMenu\Models\MenuItem;

it('retrieves a menu by location', function () {
    $menu = Menu::factory()->create(['location' => 'header']);
    MenuItem::factory()->create(['menu_id' => $menu->id]);

    $helper = new FilamentMenu;
    $result = $helper->getByLocation('header');

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($menu->id)
        ->and($result->items)->toHaveCount(1);
});

it('returns null for unknown location', function () {
    $helper = new FilamentMenu;

    expect($helper->getByLocation('nonexistent'))->toBeNull();
});

it('retrieves a menu by slug', function () {
    $menu = Menu::factory()->create(['slug' => 'main-nav']);
    MenuItem::factory()->create(['menu_id' => $menu->id]);

    $helper = new FilamentMenu;
    $result = $helper->getBySlug('main-nav');

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($menu->id)
        ->and($result->items)->toHaveCount(1);
});

it('returns null for unknown slug', function () {
    $helper = new FilamentMenu;

    expect($helper->getBySlug('nonexistent'))->toBeNull();
});
