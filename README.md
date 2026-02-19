# Create menus with ease in Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/filament-menu.svg?style=flat-square)](https://packagist.org/packages/bambamboole/filament-menu)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bambamboole/filament-menu/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bambamboole/filament-menu/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bambamboole/filament-menu/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bambamboole/filament-menu/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/filament-menu.svg?style=flat-square)](https://packagist.org/packages/bambamboole/filament-menu)

A powerful menu builder plugin for Filament that lets you create and manage navigation menus directly from your admin panel. Build unlimited nested menus, link to your Eloquent models, and render them anywhere in your Laravel application.

## Features

✨ **Intuitive Menu Builder** - Create and manage menus through a clean Filament interface  
🎯 **Multiple Menu Locations** - Define different menus for header, footer, sidebar, etc.  
🔗 **Link to Anything** - Connect menu items to custom URLs or your existing Eloquent models  
🌳 **Unlimited Nesting** - Build multi-level navigation structures with drag-and-drop ordering  
⚡ **Optimized Performance** - Built-in caching support for lightning-fast menu rendering  
🎨 **Blade Components** - Simple `<x-filament-menu::menu>` component for easy integration

## Installation

You can install the package via composer:

```bash
composer require bambamboole/filament-menu
```

> [!IMPORTANT]
> If you have not set up a custom theme and are using Filament Panels follow the instructions in the [Filament Docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

After setting up a custom theme add the plugin's views to your theme css file or your app's css file if using the standalone packages.

```css
@source '../../../../vendor/bambamboole/filament-menu/resources/**/*.blade.php';
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-menu-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-menu-config"
```

Optionally, you can publish the views using:

```bash
php artisan vendor:publish --tag="filament-menu-views"
```

## Usage

### Basic Usage

Once installed, navigate to your Filament admin panel where you'll find the **Menus** resource. Create a new menu, add menu items, and organize them as needed.

To display a menu in your Blade templates, use the provided component:

```blade
{{-- Display menu by location --}}
<x-filament-menu::menu location="header" />

{{-- Or display by slug --}}
<x-filament-menu::menu slug="main-navigation" />
```

### Registering the Plugin

In your Filament Panel provider (typically `app/Providers/Filament/AdminPanelProvider.php`), register the plugin:

```php
use Bambamboole\FilamentMenu\FilamentMenuPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentMenuPlugin::make()
                ->locations([
                    'header' => 'Header Navigation',
                    'footer' => 'Footer Links',
                    'sidebar' => 'Sidebar Menu',
                ]),
        ]);
}
```

### Working with Linkable Models

You can link menu items to your existing Eloquent models by implementing the `Linkable` interface:

```php
use Bambamboole\FilamentMenu\Contracts\Linkable;
use Illuminate\Database\Eloquent\Builder;

class Page extends Model implements Linkable
{
    public static function getLinkableQuery(): Builder
    {
        return static::query()->where('published', true);
    }

    public static function getNameColumn(): string
    {
        return 'title';
    }

    public function getLink(): string
    {
        return route('pages.show', $this->slug);
    }
}
```

Then register your linkable models with the plugin:

```php
FilamentMenuPlugin::make()
    ->linkables([
        \App\Models\Page::class,
        \App\Models\Post::class,
        \App\Models\Category::class,
    ]);
```

### Accessing Menus Programmatically

You can retrieve menus in your code using the `Menu` model:

```php
use Bambamboole\FilamentMenu\Models\Menu;

// Get menu by location
$menu = Menu::findByLocation('header');

// Get menu by slug
$menu = Menu::findBySlug('main-navigation');

// Get menu tree as array
$tree = $menu->getTree();

// Get menu tree items as collection
$items = $menu->getTreeItems();
```

### Caching

Enable caching for improved performance:

```php
FilamentMenuPlugin::make()
    ->cacheFor(3600) // Cache for 1 hour
```

### Authorization

Restrict access to the menu management resource:

```php
FilamentMenuPlugin::make()
    ->canAccess(fn () => auth()->user()->can('manage-menus'))
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Manuel Christlieb](https://github.com/bambamboole)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
