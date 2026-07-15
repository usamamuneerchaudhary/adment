<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usamamuneerchaudhary\Adment\Contracts\ManagesAds;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;
use Usamamuneerchaudhary\Adment\Support\AdsManager;

class AdmentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('adment')
            ->hasConfigFile()
            ->hasViews('adment')
            ->hasMigration('create_ads_table');
    }

    public function packageRegistered(): void
    {
        $this->app->scoped(AdsSettings::class, function (Application $app): AdsSettings {
            return new AdsSettings(
                $app['db']->connection(),
                $app['cache']->store(),
            );
        });

        $this->app->scoped(ManagesAds::class, function (Application $app): AdsManager {
            return new AdsManager(
                $app['view'],
                $app->make(AdsSettings::class),
            );
        });

        $this->app->alias(ManagesAds::class, 'adment');
    }

    public function packageBooted(): void
    {
        Blade::componentNamespace('Usamamuneerchaudhary\\Adment\\View\\Components', 'adment');

        if (config('adment.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (config('adment.api.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }
}
