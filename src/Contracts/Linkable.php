<?php

namespace Bambamboole\FilamentMenu\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Linkable
{
    public static function getLinkableLabel(): string;

    /** @return Builder<static> */
    public static function getLinkableQuery(): Builder;

    public static function getNameColumn(): string;

    public function getLink(): string;

    /** @return array<int|string, string> */
    public static function getLinkableSearchResults(string $search): array;
}
