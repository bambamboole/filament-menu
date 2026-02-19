<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu;

use Bambamboole\FilamentMenu\Contracts\Linkable;
use Bambamboole\FilamentMenu\Models\Menu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FilamentMenu
{
    /** @var array<string, string> */
    protected array $locations = [];

    /** @var array<class-string<Linkable>, string> */
    protected array $linkables = [];

    public function location(string $key, string $label): static
    {
        $this->locations[$key] = $label;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @param  class-string<Linkable>  $class
     */
    public function linkable(string $class, ?string $label = null): static
    {
        $this->linkables[$class] = $label ?? Str::headline(class_basename($class));

        return $this;
    }

    /**
     * @return array<class-string<Linkable>, string>
     */
    public function getLinkables(): array
    {
        return $this->linkables;
    }

    public function getByLocation(string $location): ?Menu
    {
        $seconds = $this->getCacheTtl();

        if ($seconds === null) {
            return $this->queryByLocation($location);
        }

        return Cache::remember(
            $this->cacheKey('location', $location),
            $seconds,
            fn (): ?Menu => $this->queryByLocation($location),
        );
    }

    public function getBySlug(string $slug): ?Menu
    {
        $seconds = $this->getCacheTtl();

        if ($seconds === null) {
            return $this->queryBySlug($slug);
        }

        return Cache::remember(
            $this->cacheKey('slug', $slug),
            $seconds,
            fn (): ?Menu => $this->queryBySlug($slug),
        );
    }

    public function flush(): void
    {
        Cache::forget('filament-menu:all-keys');

        foreach (Cache::get('filament-menu:all-keys', []) as $key) {
            Cache::forget($key);
        }
    }

    private function queryByLocation(string $location): ?Menu
    {
        return Menu::query()
            ->where('location', $location)
            ->with('items.linkable')
            ->first();
    }

    private function queryBySlug(string $slug): ?Menu
    {
        return Menu::query()
            ->where('slug', $slug)
            ->with('items.linkable')
            ->first();
    }

    private function getCacheTtl(): ?int
    {
        try {
            return FilamentMenuPlugin::get()->getCacheTtl();
        } catch (\Throwable) {
            return null;
        }
    }

    private function cacheKey(string $type, string $value): string
    {
        $key = "filament-menu:{$type}:{$value}";

        // Track keys for flushing
        $allKeys = Cache::get('filament-menu:all-keys', []);

        if (!in_array($key, $allKeys)) {
            $allKeys[] = $key;
            Cache::forever('filament-menu:all-keys', $allKeys);
        }

        return $key;
    }
}
