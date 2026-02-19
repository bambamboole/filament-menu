<?php

use Bambamboole\FilamentMenu\Models\Menu;
use Bambamboole\FilamentMenu\Models\MenuItem;
use Bambamboole\FilamentMenu\Tests\Fixtures\LinkablePage;

it('auto-generates slug from name on creation', function () {
    $menu = Menu::create(['name' => 'Main Navigation']);

    expect($menu->slug)->toBe('main-navigation');
});

it('does not overwrite an explicit slug', function () {
    $menu = Menu::create(['name' => 'Main Navigation', 'slug' => 'custom-slug']);

    expect($menu->slug)->toBe('custom-slug');
});

it('has many items ordered by sort_order', function () {
    $menu = Menu::factory()->create();
    MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Second', 'sort_order' => 1]);
    MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'First', 'sort_order' => 0]);

    $items = $menu->items;

    expect($items)->toHaveCount(2)
        ->and($items->first()->label)->toBe('First')
        ->and($items->last()->label)->toBe('Second');
});

it('returns only root items', function () {
    $menu = Menu::factory()->create();
    $parent = MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Parent']);
    MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Child', 'parent_id' => $parent->id]);

    expect($menu->rootItems)->toHaveCount(1)
        ->and($menu->rootItems->first()->label)->toBe('Parent');
});

it('builds a nested tree structure', function () {
    $menu = Menu::factory()->create();
    $parent = MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Parent', 'sort_order' => 0]);
    MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Child', 'parent_id' => $parent->id, 'sort_order' => 0]);
    MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Sibling', 'sort_order' => 1]);

    $tree = $menu->getTree();

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Parent')
        ->and($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label'])->toBe('Child')
        ->and($tree[1]['label'])->toBe('Sibling')
        ->and($tree[1]['children'])->toBeEmpty();
});

it('cascades deletes to items', function () {
    $menu = Menu::factory()->create();
    MenuItem::factory()->create(['menu_id' => $menu->id]);

    $menu->delete();

    expect(MenuItem::count())->toBe(0);
});

it('can assign a location to a menu', function () {
    $menu = Menu::factory()->create();
    $menu->update(['location' => 'footer']);

    expect($menu->refresh()->location)->toBe('footer');
});

it('resolves url from linkable model', function () {
    $page = LinkablePage::forceCreate(['title' => 'About', 'slug' => 'about']);
    $menu = Menu::factory()->create();

    $item = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'linkable_type' => LinkablePage::class,
        'linkable_id' => $page->id,
        'url' => null,
    ]);

    expect($item->getUrl())->toBe('/pages/about');
});

it('falls back to url when no linkable is set', function () {
    $item = MenuItem::factory()->create(['url' => 'https://example.com']);

    expect($item->getUrl())->toBe('https://example.com');
});

it('resolves linkable urls in tree', function () {
    $page = LinkablePage::forceCreate(['title' => 'About', 'slug' => 'about']);
    $menu = Menu::factory()->create();

    MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'linkable_type' => LinkablePage::class,
        'linkable_id' => $page->id,
        'url' => null,
        'sort_order' => 0,
    ]);

    $tree = $menu->getTree();

    expect($tree[0]['url'])->toBe('/pages/about');
});
