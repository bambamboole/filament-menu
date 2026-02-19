<?php
declare(strict_types=1);
namespace Bambamboole\FilamentMenu;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentMenuServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-menu';

    public static string $viewNamespace = 'filament-menu';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews(static::$viewNamespace)
            ->hasTranslations()
            ->hasMigrations($this->getMigrations())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('bambamboole/filament-menu');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(FilamentMenu::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            [Js::make('filament-menu', __DIR__.'/../resources/dist/filament-menu.js')],
            'bambamboole/filament-menu'
        );
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_menus_table',
            'create_menu_items_table',
        ];
    }
}
