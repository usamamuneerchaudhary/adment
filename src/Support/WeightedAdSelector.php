<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Support;

use Illuminate\Support\Collection;
use Usamamuneerchaudhary\Adment\Models\Ad;

class WeightedAdSelector
{
    /**
     * Pick a single ad using the order column as a weight (minimum 1).
     *
     * @param  Collection<int, Ad>  $ads
     */
    public function select(Collection $ads): ?Ad
    {
        if ($ads->isEmpty()) {
            return null;
        }

        if ($ads->count() === 1) {
            return $ads->first();
        }

        $total = (int) $ads->sum(fn (Ad $ad): int => $this->weight($ad));

        if ($total < 1) {
            return $ads->first();
        }

        $pick = random_int(1, $total);
        $running = 0;

        foreach ($ads as $ad) {
            $running += $this->weight($ad);

            if ($pick <= $running) {
                return $ad;
            }
        }

        return $ads->last();
    }

    /** Normalize an ad's order column into a positive weight. */
    public function weight(Ad $ad): int
    {
        return max(1, (int) $ad->order);
    }
}
