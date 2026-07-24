<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Usamamuneerchaudhary\Adment\Filament\Pages\ManageAdsSettings;
use Usamamuneerchaudhary\Adment\Filament\Resources\AdResource;

class AdmentPlugin implements Plugin
{
    protected bool $hasSettingsPage = true;

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

    /** Register the ad resource and optional settings page on the panel. */
    public function register(Panel $panel): void
    {
        $panel->resources([
            AdResource::class,
        ]);

        if ($this->hasSettingsPage) {
            $panel->pages([
                ManageAdsSettings::class,
            ]);
        }
    }

    /** Boot hook for panel-level plugin setup (unused). */
    public function boot(Panel $panel): void
    {
        //
    }
}
