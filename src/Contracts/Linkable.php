<?php

namespace Bambamboole\FilamentMenu\Contracts;

interface Linkable
{
    public static function getNameColumn(): string;

    public function getUrl(): string;
}
