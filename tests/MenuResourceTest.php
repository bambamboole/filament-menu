<?php declare(strict_types=1);

use Bambamboole\FilamentMenu\Filament\Resources\MenuResource;
use Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages\EditMenu;
use Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages\ListMenus;
use Bambamboole\FilamentMenu\FilamentMenuPlugin;
use Bambamboole\FilamentMenu\Models\Menu;
use Bambamboole\FilamentMenu\Models\MenuItem;
use Bambamboole\FilamentMenu\Tests\Fixtures\LinkablePage;
use Bambamboole\FilamentMenu\Tests\Fixtures\TestUser;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(TestUser::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]));
});

it('can render the list page', function () {
    $this->get(MenuResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list menus', function () {
    $menus = Menu::factory()->count(3)->create();

    livewire(ListMenus::class)
        ->assertCanSeeTableRecords($menus);
});

it('can create a menu via modal', function () {
    livewire(ListMenus::class)
        ->callAction('create', [
            'name' => 'Main Menu',
            'slug' => 'main-menu',
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('menus', [
        'name' => 'Main Menu',
        'slug' => 'main-menu',
    ]);
});

it('validates required fields on create', function () {
    livewire(ListMenus::class)
        ->callAction('create', [
            'name' => '',
            'slug' => '',
        ])
        ->assertHasActionErrors(['name' => 'required', 'slug' => 'required']);
});

it('can render the edit page', function () {
    $menu = Menu::factory()->create();

    $this->get(MenuResource::getUrl('edit', ['record' => $menu]))
        ->assertSuccessful();
});

it('can update menu name and slug', function () {
    $menu = Menu::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->fillForm([
            'name' => 'New Name',
            'slug' => 'new-name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($menu->refresh())
        ->name->toBe('New Name')
        ->slug->toBe('new-name');
});

it('can add a custom menu item', function () {
    $menu = Menu::factory()->create();

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction(TestAction::make('addCustomLink')->schemaComponent('add-menu-item-actions', schema: 'content'), [
            'label' => 'Home',
            'url' => 'https://example.com',
            'target' => '_self',
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('menu_items', [
        'menu_id' => $menu->id,
        'label' => 'Home',
        'url' => 'https://example.com',
    ]);
});

it('can edit a custom menu item', function () {
    $menu = Menu::factory()->create();
    $item = MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Old Label', 'url' => '/old']);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction(TestAction::make('editItem')->arguments(['itemId' => $item->id]), [
            'label' => 'New Label',
            'url' => '/new',
            'target' => '_self',
            'item_type' => 'custom',
        ])
        ->assertHasNoActionErrors();

    expect($item->refresh()->label)->toBe('New Label');
});

it('can add a linked menu item', function () {
    $menu = Menu::factory()->create();
    $page = LinkablePage::forceCreate(['title' => 'About', 'slug' => 'about']);
    $key = str_replace('\\', '_', strtolower(LinkablePage::class));

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction(TestAction::make("addLinkable_{$key}")->schemaComponent('add-menu-item-actions', schema: 'content'), [
            'linkable_id' => $page->id,
            'label' => 'About',
            'target' => '_self',
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('menu_items', [
        'menu_id' => $menu->id,
        'label' => 'About',
        'linkable_type' => LinkablePage::class,
        'linkable_id' => $page->id,
    ]);
});

it('can edit a linked menu item', function () {
    $menu = Menu::factory()->create();
    $page = LinkablePage::forceCreate(['title' => 'About', 'slug' => 'about']);
    $item = MenuItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Old Label',
        'linkable_type' => LinkablePage::class,
        'linkable_id' => $page->id,
    ]);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction(TestAction::make('editItem')->arguments(['itemId' => $item->id]), [
            'label' => 'New Label',
            'target' => '_self',
            'item_type' => 'linkable',
            'linkable_type' => LinkablePage::class,
            'linkable_id' => $page->id,
        ])
        ->assertHasNoActionErrors();

    expect($item->refresh()->label)->toBe('New Label');
});

it('can delete a menu item', function () {
    $menu = Menu::factory()->create();
    $item = MenuItem::factory()->create(['menu_id' => $menu->id]);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction(TestAction::make('deleteItem')->arguments(['itemId' => $item->id]));

    $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
});

it('can reorder the tree', function () {
    $menu = Menu::factory()->create();
    $item1 = MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'First', 'sort_order' => 0]);
    $item2 = MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Second', 'sort_order' => 1]);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->call('reorderTree', [
            ['id' => $item2->id, 'children' => []],
            ['id' => $item1->id, 'children' => []],
        ]);

    expect($item2->refresh()->sort_order)->toBe(0)
        ->and($item1->refresh()->sort_order)->toBe(1);
});

it('can nest items via reorder', function () {
    $menu = Menu::factory()->create();
    $parent = MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Parent', 'sort_order' => 0]);
    $child = MenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Child', 'sort_order' => 1]);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->call('reorderTree', [
            ['id' => $parent->id, 'children' => [
                ['id' => $child->id, 'children' => []],
            ]],
        ]);

    expect($child->refresh())
        ->parent_id->toBe($parent->id);
});

it('can delete menu from edit page', function () {
    $menu = Menu::factory()->create();

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction('delete');

    $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
});

it('denies access when canAccess callback returns false', function () {
    FilamentMenuPlugin::get()->canAccess(fn () => false);

    $this->get(MenuResource::getUrl('index'))
        ->assertForbidden();
});

it('allows access when canAccess callback returns true', function () {
    FilamentMenuPlugin::get()->canAccess(fn () => true);

    $this->get(MenuResource::getUrl('index'))
        ->assertSuccessful();
});

// Security: cross-menu item operations

it('cannot delete an item belonging to another menu', function () {
    $menuA = Menu::factory()->create();
    $menuB = Menu::factory()->create();
    $itemFromB = MenuItem::factory()->create(['menu_id' => $menuB->id]);

    livewire(EditMenu::class, ['record' => $menuA->id])
        ->callAction(TestAction::make('deleteItem')->arguments(['itemId' => $itemFromB->id]));

    $this->assertDatabaseHas('menu_items', ['id' => $itemFromB->id]);
});

it('cannot reorder items belonging to another menu', function () {
    $menuA = Menu::factory()->create();
    $menuB = Menu::factory()->create();
    $itemFromB = MenuItem::factory()->create(['menu_id' => $menuB->id, 'sort_order' => 0]);

    livewire(EditMenu::class, ['record' => $menuA->id])
        ->call('reorderTree', [
            ['id' => $itemFromB->id, 'children' => []],
        ]);

    expect($itemFromB->refresh()->sort_order)->toBe(0);
});

// Location assignment

it('can assign a location via form', function () {
    $menu = Menu::factory()->create();

    livewire(EditMenu::class, ['record' => $menu->id])
        ->fillForm(['location' => 'header'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($menu->refresh()->location)->toBe('header');
});
