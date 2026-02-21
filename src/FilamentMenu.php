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

    /** @var array<string, string> */
    protected array $locales = [];

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

    /**
     * @param  array<string, string>  $locales
     */
    public function setLocales(array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    public function hasLocales(): bool
    {
        return $this->locales !== [];
    }

    public function getByLocation(string $location, ?string $locale = null): ?Menu
    {
        $locale = $this->resolveLocale($locale);
        $seconds = $this->getCacheTtl();

        if ($seconds === null) {
            return $this->queryByLocation($location, $locale);
        }

        $cacheKeySuffix = $locale !== null ? "{$location}:{$locale}" : $location;

        return Cache::remember(
            $this->cacheKey('location', $cacheKeySuffix),
            $seconds,
            fn (): ?Menu => $this->queryByLocation($location, $locale),
        );
    }

    public function flush(): void
    {
        Cache::forget('filament-menu:all-keys');

        foreach (Cache::get('filament-menu:all-keys', []) as $key) {
            Cache::forget($key);
        }
    }

    private function queryByLocation(string $location, ?string $locale): ?Menu
    {
        return Menu::query()
            ->where('location', $location)
            ->when($locale !== null, fn ($query) => $query->where('locale', $locale))
            ->with('items.linkable')
            ->first();
    }

    private function resolveLocale(?string $locale): ?string
    {
        if (!$this->hasLocales()) {
            return null;
        }

        return $locale ?? app()->getLocale();
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
