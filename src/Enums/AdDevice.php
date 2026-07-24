<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdDevice: string implements HasLabel
{
    case Desktop = 'desktop';
    case Tablet = 'tablet';
    case Mobile = 'mobile';

    /** Return the human-readable device label. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Desktop => __('Desktop'),
            self::Tablet => __('Tablet'),
            self::Mobile => __('Mobile'),
        };
    }
}
