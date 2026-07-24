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
use Usamamuneerchaudhary\Adment\Support\AdTargeting;
use Usamamuneerchaudhary\Adment\Support\WeightedAdSelector;

class AdmentServiceProvider extends PackageServiceProvider
{
    /** Configure the package name, config, views, and migration. */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('adment')
            ->hasConfigFile()
            ->hasViews('adment')
            ->hasMigration('create_ads_table')
            ->hasMigration('add_adment_analytics_and_targeting_columns');
    }

    /** Bind scoped ads settings and manager services into the container. */
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
                $app->make(AdTargeting::class),
                $app->make(WeightedAdSelector::class),
            );
        });

        $this->app->alias(ManagesAds::class, 'adment');
    }

    /** Register Blade components and load optional web/API routes. */
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
