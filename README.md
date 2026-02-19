# Filament Menu

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/filament-menu.svg?style=flat-square)](https://packagist.org/packages/bambamboole/filament-menu)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bambamboole/filament-menu/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bambamboole/filament-menu/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/filament-menu.svg?style=flat-square)](https://packagist.org/packages/bambamboole/filament-menu)

A menu builder plugin for [Filament](https://filamentphp.com) that lets you create and manage navigation menus with
drag-and-drop ordering, nesting, and linkable Eloquent models.

## Installation

```bash
composer require bambamboole/filament-menu
```

Add the plugin views to your custom theme's CSS file:

```css
@source '../../../../vendor/bambamboole/filament-menu/resources/**/*.blade.php';
```

## Usage

Register the plugin in your panel provider:

```php
use Bambamboole\FilamentMenu\FilamentMenuPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentMenuPlugin::make()
                ->locations(['header', 'footer', 'sidebar'])
                ->linkables([Page::class, Post::class])
                ->canAccess(fn () => auth()->user()->isAdmin())
                ->cacheFor(3600),
        ]);
}
```

| Method               | Description                                                       |
|----------------------|-------------------------------------------------------------------|
| `locations(array)`   | Named positions where menus can be assigned (e.g. header, footer) |
| `linkables(array)`   | Eloquent models that can be linked as menu items                  |
| `canAccess(Closure)` | Controls who can manage menus                                     |
| `cacheFor(int)`      | Cache duration in seconds (auto-invalidated on changes)           |

## Linkable Models

To let editors link menu items to your Eloquent models, implement the `Linkable` interface:

```php
use Bambamboole\FilamentMenu\Contracts\Linkable;
use Bambamboole\FilamentMenu\Concerns\IsLinkable;
use Illuminate\Database\Eloquent\Builder;

class Page extends Model implements Linkable
{
    use IsLinkable;

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

The `IsLinkable` trait provides sensible defaults — override only what you need.

## Rendering Menus

Use the Blade component in your templates:

```blade
<x-filament-menu::menu location="header" />

<x-filament-menu::menu slug="main-navigation" />
```

Or retrieve menus programmatically:

```php
use Bambamboole\FilamentMenu\Models\Menu;

$menu = Menu::findByLocation('header');
$tree = $menu->getTree(); // array of nested items
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
