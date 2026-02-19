<?php

namespace Bambamboole\FilamentMenu;

use Bambamboole\FilamentMenu\Filament\Resources\MenuResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMenuPlugin implements Plugin
{
    /** @var array<int, string> */
    protected array $locations = [];

    protected ?Closure $canAccess = null;

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

    /**
     * @param  array<int, string>  $locations
     */
    public function locations(array $locations): static
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getLocations(): array
    {
        return $this->locations;
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
