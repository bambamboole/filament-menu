<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @mixin Model */
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
        return $this->getAttribute('url');
    }
}
