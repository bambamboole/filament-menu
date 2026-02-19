<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Facades;

use Bambamboole\FilamentMenu\Models\Menu;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Bambamboole\FilamentMenu\FilamentMenu location(string $key, string $label)
 * @method static array<string, string> getLocations()
 * @method static \Bambamboole\FilamentMenu\FilamentMenu linkable(string $class, ?string $label = null)
 * @method static array<class-string<\Bambamboole\FilamentMenu\Contracts\Linkable>, string> getLinkables()
 * @method static Menu|null getByLocation(string $location)
 * @method static Menu|null getBySlug(string $slug)
 * @method static void flush()
 *
 * @see \Bambamboole\FilamentMenu\FilamentMenu
 */
class FilamentMenu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bambamboole\FilamentMenu\FilamentMenu::class;
    }
}
