<?php

namespace Bambamboole\FilamentMenu\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Menu $menu): void {
            if (empty($menu->slug)) {
                $menu->slug = Str::slug($menu->name);
            }
        });
    }

    /** @return HasMany<MenuItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /** @return HasMany<MenuItem, $this> */
    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<MenuLocation, $this> */
    public function locations(): HasMany
    {
        return $this->hasMany(MenuLocation::class);
    }

    /**
     * @return array<int, array{id: int, label: string, url: ?string, target: ?string, type: string, children: array<int, mixed>}>
     */
    public function getTree(): array
    {
        $items = $this->items()->with('linkable')->get();
        $grouped = $items->groupBy('parent_id');

        return $this->buildTree($grouped, null);
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, \Illuminate\Support\Collection<int, MenuItem>>  $grouped
     * @return array<int, array{id: int, label: string, url: ?string, target: ?string, type: string, children: array<int, mixed>}>
     */
    private function buildTree($grouped, ?int $parentId): array
    {
        $branch = [];

        foreach ($grouped->get($parentId ?? '', collect()) as $item) {
            $branch[] = [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $item->getUrl(),
                'target' => $item->target,
                'type' => $item->type,
                'children' => $this->buildTree($grouped, $item->id),
            ];
        }

        return $branch;
    }
}
