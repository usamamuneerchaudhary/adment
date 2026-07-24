<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Usamamuneerchaudhary\Adment\AdmentPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugin(AdmentPlugin::make());
    }
}
