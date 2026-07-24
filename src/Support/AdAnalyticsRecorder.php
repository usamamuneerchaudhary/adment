<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Support;

use Illuminate\Support\Facades\DB;
use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Models\AdDailyStat;

class AdAnalyticsRecorder
{
    /** Increment lifetime impressions and today's daily stat row. */
    public function recordImpression(Ad $ad): void
    {
        Ad::withoutEvents(
            fn () => Ad::withoutTimestamps(
                fn () => $ad->newQuery()->whereKey($ad->getKey())->increment('impressions'),
            ),
        );

        $this->bumpDailyStat($ad, 'impressions');
        $ad->refresh();
    }

    /** Increment lifetime clicks and today's daily stat row. */
    public function recordClick(Ad $ad): void
    {
        Ad::withoutEvents(
            fn () => Ad::withoutTimestamps(
                fn () => $ad->newQuery()->whereKey($ad->getKey())->increment('clicked'),
            ),
        );

        $this->bumpDailyStat($ad, 'clicks');
        $ad->refresh();
    }

    /** Upsert and increment a daily stats counter for today. */
    protected function bumpDailyStat(Ad $ad, string $column): void
    {
        $date = now()->toDateString();
        $table = (new AdDailyStat)->getTable();

        $existing = DB::table($table)
            ->where('ad_id', $ad->getKey())
            ->where('date', $date)
            ->first();

        if ($existing === null) {
            DB::table($table)->insert([
                'ad_id' => $ad->getKey(),
                'date' => $date,
                'impressions' => $column === 'impressions' ? 1 : 0,
                'clicks' => $column === 'clicks' ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table($table)
            ->where('id', $existing->id)
            ->update([
                $column => DB::raw("{$column} + 1"),
                'updated_at' => now(),
            ]);
    }
}
