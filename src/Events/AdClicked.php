<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Usamamuneerchaudhary\Adment\Models\Ad;

class AdClicked
{
    use Dispatchable;

    /** Create an event for a recorded ad click. */
    public function __construct(
        public Ad $ad,
        public ?string $referer = null,
        public ?string $userAgent = null,
    ) {}
}
