<?php

namespace Bambamboole\FilamentMenu\Models;

use Bambamboole\FilamentMenu\Contracts\Linkable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'url',
        'target',
        'type',
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
            return $this->linkable->getLink();
        }

        return $this->url;
    }
}
