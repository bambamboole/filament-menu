<?php declare(strict_types=1);

use Bambamboole\FilamentMenu\FilamentMenu;
use Bambamboole\FilamentMenu\FilamentMenuPlugin;
use Bambamboole\FilamentMenu\Models\Menu;
use Bambamboole\FilamentMenu\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

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

it('flushes all cached menu entries', function () {
    FilamentMenuPlugin::get()->cacheFor(3600);

    $menu = Menu::factory()->create(['location' => 'header']);
    MenuItem::factory()->create(['menu_id' => $menu->id]);

    $helper = app(FilamentMenu::class);

    // Populate the cache
    $result = $helper->getByLocation('header');
    expect($result)->not->toBeNull();

    $trackedKeys = Cache::get('filament-menu:all-keys', []);
    expect($trackedKeys)->not->toBeEmpty();

    // Flush and verify all entries are gone
    $helper->flush();

    expect(Cache::get('filament-menu:all-keys'))->toBeNull();

    foreach ($trackedKeys as $key) {
        expect(Cache::get($key))->toBeNull();
    }
});
