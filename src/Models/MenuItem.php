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
use Illuminate\Support\Str;

class MenuItem extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn () => app(FilamentMenu::class)->flush());
        static::deleted(fn () => app(FilamentMenu::class)->flush());
    }

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

    /** @return MorphTo<Model&Linkable, $this> */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrl(): ?string
    {
        if ($this->linkable instanceof Linkable) {
            $url = $this->linkable->getLink();
        } else {
            $url = $this->url;
        }

        return $this->prefixLocale($url);
    }

    private function prefixLocale(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        if (str_starts_with($url, 'http') || str_starts_with($url, '#') || str_starts_with($url, 'mailto:')) {
            return $url;
        }

        $locale = $this->menu?->locale;

        if ($locale === null) {
            return $url;
        }

        return '/' . $locale . Str::start($url, '/');
    }
}
