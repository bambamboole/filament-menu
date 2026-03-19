<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Models;

use Bambamboole\FilamentMenu\FilamentMenu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'location',
        'locale',
    ];

    protected static function booted(): void
    {
        static::creating(function (Menu $menu): void {
            if (empty($menu->slug)) {
                $menu->slug = Str::slug($menu->name);
            }
        });

        static::saved(fn () => app(FilamentMenu::class)->flush());
        static::deleted(fn () => app(FilamentMenu::class)->flush());
    }

    public static function findByLocation(string $location, ?string $locale = null): ?self
    {
        return app(FilamentMenu::class)->getByLocation($location, $locale);
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

    /**
     * Load all items flat and build a nested collection of MenuItem models.
     * Unlike eager-loading children.children.children, this supports unlimited depth.
     *
     * @return Collection<int, MenuItem>
     */
    public function getTreeItems(): Collection
    {
        $items = $this->items()->with('linkable')->get();
        $grouped = $items->groupBy(fn (MenuItem $item): int => $item->parent_id ?? 0);

        $this->buildTreeRelations($grouped, 0);

        return $grouped->get(0, collect());
    }

    /**
     * Recursively set the 'children' relation on each MenuItem from the grouped collection.
     *
     * @param  \Illuminate\Support\Collection<int, Collection<int, MenuItem>>  $grouped
     */
    private function buildTreeRelations($grouped, int $parentId): void
    {
        foreach ($grouped->get($parentId, collect()) as $item) {
            $children = $grouped->get($item->id, collect());
            $item->setRelation('children', $children);

            if ($children->isNotEmpty()) {
                $this->buildTreeRelations($grouped, $item->id);
            }
        }
    }

    /**
     * @return array<int, array{id: int, label: string, url: ?string, target: ?string, children: array<int, mixed>}>
     */
    public function getTree(): array
    {
        return $this->treeToArray($this->getTreeItems());
    }

    /**
     * @param  Collection<int, MenuItem>|\Illuminate\Support\Collection<int, MenuItem>  $items
     * @return array<int, array{id: int, label: string, url: ?string, target: ?string, children: array<int, mixed>}>
     */
    private function treeToArray(\Illuminate\Support\Collection $items): array
    {
        return $items->map(fn (MenuItem $item): array => [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->getUrl(),
            'target' => $item->target,
            'children' => $this->treeToArray($item->children),
        ])->all();
    }
}
