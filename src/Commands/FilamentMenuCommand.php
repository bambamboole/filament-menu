<?php

namespace Bambamboole\FilamentMenu\Commands;

use Illuminate\Console\Command;

class FilamentMenuCommand extends Command
{
    public $signature = 'filament-menu';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
