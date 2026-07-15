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

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'adment';
    }

    public function settingsPage(bool $condition = true): static
    {
        $this->hasSettingsPage = $condition;

        return $this;
    }

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

    public function boot(Panel $panel): void
    {
        //
    }
}
