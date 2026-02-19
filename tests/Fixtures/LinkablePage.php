<?php

namespace Bambamboole\FilamentMenu\Tests\Fixtures;

use Bambamboole\FilamentMenu\Contracts\IsLinkable;
use Bambamboole\FilamentMenu\Contracts\Linkable;
use Illuminate\Database\Eloquent\Model;

class LinkablePage extends Model implements Linkable
{
    use IsLinkable;

    protected $table = 'pages';

    protected $guarded = [];

    public static function getNameColumn(): string
    {
        return 'title';
    }

    public function getLink(): string
    {
        return '/pages/'.$this->slug;
    }
}
