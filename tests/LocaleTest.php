<?php declare(strict_types=1);

use Bambamboole\FilamentMenu\FilamentMenu;
use Bambamboole\FilamentMenu\Models\Menu;
use Bambamboole\FilamentMenu\Models\MenuItem;

it('filters menus by locale when locales are configured', function () {
    $service = app(FilamentMenu::class);
    $service->setLocales(['en' => 'English', 'de' => 'Deutsch']);

    Menu::factory()->create(['location' => 'header', 'locale' => 'en']);
    Menu::factory()->create(['location' => 'header', 'locale' => 'de']);

    app()->setLocale('en');
    $menu = $service->getByLocation('header');
    expect($menu)->not->toBeNull()
        ->and($menu->locale)->toBe('en');

    app()->setLocale('de');
    $menu = $service->getByLocation('header');
    expect($menu)->not->toBeNull()
        ->and($menu->locale)->toBe('de');
});

it('does not filter by locale when locales are not configured', function () {
    $service = app(FilamentMenu::class);

    $menu = Menu::factory()->create(['location' => 'header']);

    $result = $service->getByLocation('header');
    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($menu->id);
});

it('allows explicit locale override in getByLocation', function () {
    $service = app(FilamentMenu::class);
    $service->setLocales(['en' => 'English', 'de' => 'Deutsch']);

    Menu::factory()->create(['location' => 'header', 'locale' => 'en']);
    $deMenu = Menu::factory()->create(['location' => 'header', 'locale' => 'de']);

    app()->setLocale('en');
    $menu = $service->getByLocation('header', 'de');
    expect($menu)->not->toBeNull()
        ->and($menu->id)->toBe($deMenu->id);
});

it('prefixes relative URLs with menu locale', function () {
    $menu = Menu::factory()->create(['locale' => 'de']);
    $item = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'url' => '/features',
    ]);

    $item->setRelation('menu', $menu);

    expect($item->getUrl())->toBe('/features');
});

it('does not prefix absolute URLs', function () {
    $menu = Menu::factory()->create(['locale' => 'de']);
    $item = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'url' => 'https://example.com',
    ]);

    $item->setRelation('menu', $menu);

    expect($item->getUrl())->toBe('https://example.com');
});

it('does not prefix anchor-only URLs', function () {
    $menu = Menu::factory()->create(['locale' => 'en']);
    $item = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'url' => '#section',
    ]);

    $item->setRelation('menu', $menu);

    expect($item->getUrl())->toBe('#section');
});

it('does not prefix when menu has no locale', function () {
    $menu = Menu::factory()->create(['locale' => null]);
    $item = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'url' => '/features',
    ]);

    $item->setRelation('menu', $menu);

    expect($item->getUrl())->toBe('/features');
});

it('does not prefix mailto URLs', function () {
    $menu = Menu::factory()->create(['locale' => 'de']);
    $item = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'url' => 'mailto:test@example.com',
    ]);

    $item->setRelation('menu', $menu);

    expect($item->getUrl())->toBe('mailto:test@example.com');
});

it('includes locale in tree URLs', function () {
    $menu = Menu::factory()->create(['locale' => 'de']);
    MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'url' => '/about',
        'sort_order' => 0,
    ]);

    $tree = $menu->getTree();

    expect($tree[0]['url'])->toBe('/about');
});
