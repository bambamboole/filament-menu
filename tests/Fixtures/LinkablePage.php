<?php

namespace Bambamboole\FilamentMenu\Tests\Fixtures;

use Bambamboole\FilamentMenu\Contracts\HasLinkable;
use Bambamboole\FilamentMenu\Contracts\Linkable;
use Illuminate\Database\Eloquent\Model;

class LinkablePage extends Model implements Linkable
{
    use HasLinkable;

    protected $table = 'pages';

    protected $guarded = [];

    public static function getNameColumn(): string
    {
        return 'title';
    }

    public function getUrl(): string
    {
        return '/pages/' . $this->slug;
    }
}
