<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Usamamuneerchaudhary\Adment\Filament\Pages\AdAnalyticsDashboard;
use Usamamuneerchaudhary\Adment\Filament\Pages\ManageAdsSettings;
use Usamamuneerchaudhary\Adment\Filament\Resources\AdResource;

class AdmentPlugin implements Plugin
{
    protected bool $hasSettingsPage = true;

    protected bool $hasAnalyticsPage = true;

    /** Resolve a fresh plugin instance from the container. */
    public static function make(): static
    {
        return app(static::class);
    }

    /** Return the unique Filament plugin identifier. */
    public function getId(): string
    {
        return 'adment';
    }

    /** Toggle whether the ads settings page is registered on the panel. */
    public function settingsPage(bool $condition = true): static
    {
        $this->hasSettingsPage = $condition;

        return $this;
    }

    /** Toggle whether the analytics dashboard is registered on the panel. */
    public function analyticsPage(bool $condition = true): static
    {
        $this->hasAnalyticsPage = $condition;

        return $this;
    }

    /** Register the ad resource and optional settings/analytics pages on the panel. */
    public function register(Panel $panel): void
    {
        $panel->resources([
            AdResource::class,
        ]);

        $pages = [];

        if ($this->hasSettingsPage) {
            $pages[] = ManageAdsSettings::class;
        }

        if ($this->hasAnalyticsPage) {
            $pages[] = AdAnalyticsDashboard::class;
        }

        if ($pages !== []) {
            $panel->pages($pages);
        }
    }

    /** Boot hook for panel-level plugin setup (unused). */
    public function boot(Panel $panel): void
    {
        //
    }
}
