<?php

namespace Bambamboole\FilamentMenu\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/** @mixin \Illuminate\Database\Eloquent\Model */
trait HasLinkable
{
    public static function getLinkableLabel(): string
    {
        return Str::headline(class_basename(static::class));
    }

    /** @return Builder<static> */
    public static function getLinkableQuery(): Builder
    {
        return static::query();
    }

    public static function getNameColumn(): string
    {
        return 'name';
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /** @return array<int|string, string> */
    public static function getLinkableSearchResults(string $search): array
    {
        $nameColumn = static::getNameColumn();

        return static::getLinkableQuery()
            ->where($nameColumn, 'like', "%{$search}%")
            ->pluck($nameColumn, (new static)->getKeyName())
            ->all();
    }
}
