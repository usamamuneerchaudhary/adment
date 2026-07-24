<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ad_id
 * @property Carbon $date
 * @property int $impressions
 * @property int $clicks
 * @property-read Ad $ad
 */
class AdDailyStat extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
    ];

    /** Resolve the daily stats table name from config. */
    public function getTable(): string
    {
        return config('adment.daily_stats_table', 'ad_daily_stats');
    }

    /**
     * @return BelongsTo<Ad, $this>
     */
    public function ad(): BelongsTo
    {
        /** @var class-string<Ad> $model */
        $model = config('adment.models.ad', Ad::class);

        return $this->belongsTo($model);
    }

    /** Calculate click-through rate as a percentage. */
    public function ctr(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }
}
