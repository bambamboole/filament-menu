<?php declare(strict_types=1);
namespace Bambamboole\FilamentMenu\Tests\Fixtures;

use Bambamboole\FilamentMenu\FilamentMenuPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('test')
            ->path('test')
            ->login()
            ->plugins([
                FilamentMenuPlugin::make()
                    ->locations(['header', 'footer', 'sidebar'])
                    ->linkables([LinkablePage::class]),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
