<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu;

use Bambamboole\FilamentMenu\Contracts\Linkable;
use Bambamboole\FilamentMenu\Filament\Resources\MenuResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMenuPlugin implements Plugin
{
    protected ?Closure $canAccess = null;

    protected ?int $cacheTtl = null;

    public function getId(): string
    {
        return 'filament-menu';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MenuResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function location(string $key, string $label): static
    {
        app(FilamentMenu::class)->location($key, $label);

        return $this;
    }

    /**
     * @param  class-string<Linkable>  $class
     */
    public function linkable(string $class, ?string $label = null): static
    {
        app(FilamentMenu::class)->linkable($class, $label);

        return $this;
    }

    public function canAccess(Closure $callback): static
    {
        $this->canAccess = $callback;

        return $this;
    }

    public function isAuthorized(): bool
    {
        if ($this->canAccess === null) {
            return true;
        }

        return ($this->canAccess)();
    }

    public function cacheFor(int $seconds): static
    {
        $this->cacheTtl = $seconds;

        return $this;
    }

    public function getCacheTtl(): ?int
    {
        return $this->cacheTtl;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
