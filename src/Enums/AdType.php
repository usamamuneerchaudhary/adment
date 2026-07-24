<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdType: string implements HasLabel
{
    case Custom = 'custom_ad';
    case GoogleAdsense = 'google_adsense';

    /** Return the human-readable ad type label. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Custom => __('Custom ad'),
            self::GoogleAdsense => __('Google AdSense unit'),
        };
    }
}
