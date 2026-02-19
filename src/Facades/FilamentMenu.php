<?php

namespace Bambamboole\FilamentMenu\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bambamboole\FilamentMenu\FilamentMenu
 */
class FilamentMenu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bambamboole\FilamentMenu\FilamentMenu::class;
    }
}
