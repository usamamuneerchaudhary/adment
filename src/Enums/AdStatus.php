<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AdStatus: string implements HasColor, HasLabel
{
    case Published = 'published';
    case Draft = 'draft';

    /** Return the human-readable status label. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Published => __('Published'),
            self::Draft => __('Draft'),
        };
    }

    /** Return the Filament badge color for this status. */
    public function getColor(): string
    {
        return match ($this) {
            self::Published => 'success',
            self::Draft => 'gray',
        };
    }
}
