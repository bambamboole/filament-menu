<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Linkable
{
    /** @return Builder<static> */
    public static function getLinkableQuery(): Builder;

    public static function getNameColumn(): string;

    public function getLink(): string;
}
