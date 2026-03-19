<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Models;

use Bambamboole\FilamentMenu\Contracts\Linkable;
use Bambamboole\FilamentMenu\FilamentMenu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $label
 * @property string|null $url
 * @property string|null $target
 * @property int $sort_order
 * @property string|null $linkable_type
 * @property int|null $linkable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'url',
        'target',
        'sort_order',
        'linkable_type',
        'linkable_id',
    ];

    #[\Override]
    protected static function booted(): void
    {
        static::saved(fn () => app(FilamentMenu::class)->flush());
        static::deleted(fn () => app(FilamentMenu::class)->flush());
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** @return MorphTo<Model, $this> */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrl(): ?string
    {
        if ($this->linkable instanceof Linkable) {
            return $this->linkable->getLink();
        }

        return $this->url;
    }
}
