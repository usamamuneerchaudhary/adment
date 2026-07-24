<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;
use Usamamuneerchaudhary\Adment\Models\Ad;

class AdsLoading
{
    use Dispatchable;

    /**
     * Create an event for ads loaded into the manager.
     *
     * @param  Collection<int, Ad>  $ads
     */
    public function __construct(public Collection $ads) {}
}
