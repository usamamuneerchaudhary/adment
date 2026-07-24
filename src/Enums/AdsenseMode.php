<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum AdsenseMode: string implements HasDescription, HasLabel
{
    case None = 'none';
    case Auto = 'auto';
    case Unit = 'unit';

    /** Return the human-readable AdSense mode label. */
    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('Disabled'),
            self::Auto => __('Auto Ads'),
            self::Unit => __('Ad units'),
        };
    }

    /** Return a short description of how this AdSense mode behaves. */
    public function getDescription(): string
    {
        return match ($this) {
            self::None => __('No AdSense scripts are injected.'),
            self::Auto => __('Paste the Auto Ads snippet from AdSense; Google places ads automatically.'),
            self::Unit => __('Provide your publisher client ID and render individual ad units by slot ID.'),
        };
    }
}
