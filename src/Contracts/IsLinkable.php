<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Contracts;

use Illuminate\Database\Eloquent\Builder;

/** @mixin \Illuminate\Database\Eloquent\Model */
trait IsLinkable
{
    /** @return Builder<static> */
    public static function getLinkableQuery(): Builder
    {
        return static::query();
    }

    public static function getNameColumn(): string
    {
        return 'name';
    }

    public function getLink(): string
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
