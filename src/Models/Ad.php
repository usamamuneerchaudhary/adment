<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Usamamuneerchaudhary\Adment\Database\Factories\AdFactory;
use Usamamuneerchaudhary\Adment\Enums\AdStatus;
use Usamamuneerchaudhary\Adment\Enums\AdType;

/**
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string|null $location
 * @property string|null $image
 * @property string|null $tablet_image
 * @property string|null $mobile_image
 * @property string|null $url
 * @property bool $open_in_new_tab
 * @property Carbon|null $expired_at
 * @property int $order
 * @property AdStatus $status
 * @property int $clicked
 * @property AdType $ads_type
 * @property string|null $google_adsense_slot_id
 * @property-read string|null $image_url
 * @property-read string|null $tablet_image_url
 * @property-read string|null $mobile_image_url
 * @property-read string|null $click_url
 *
 * @method static Builder<static> query()
 * @method static Builder<static> published()
 * @method static Builder<static> displayable()
 * @method static Builder<static> forLocation(string $location)
 */
class Ad extends Model
{
    /** @use HasFactory<AdFactory> */
    use HasFactory;

    protected $guarded = ['id', 'clicked'];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'expired_at' => 'datetime',
        'order' => 'integer',
        'clicked' => 'integer',
        'status' => AdStatus::class,
        'ads_type' => AdType::class,
    ];

    /** Resolve the ads table name from config. */
    public function getTable(): string
    {
        return config('adment.table', 'ads');
    }

    /** Assign a unique public key when creating an ad without one. */
    protected static function booted(): void
    {
        static::creating(function (self $ad): void {
            if (blank($ad->getAttribute('key'))) {
                $ad->key = static::generateUniqueKey();
            }
        });
    }

    /** Generate a unique uppercase random public key. */
    public static function generateUniqueKey(int $length = 12): string
    {
        do {
            $key = Str::upper(Str::random($length));
        } while (static::query()->where('key', $key)->exists());

        return $key;
    }

    /** Return the factory used to seed ad models. */
    protected static function newFactory(): AdFactory
    {
        return AdFactory::new();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Limit the query to published ads.
     *
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', AdStatus::Published);
    }

    /**
     * Limit the query to published ads that are AdSense units or not yet expired.
     *
     * @param  Builder<static>  $query
     */
    public function scopeDisplayable(Builder $query): void
    {
        $query->published()->where(function (Builder $query): void {
            $query->where('ads_type', AdType::GoogleAdsense)
                ->orWhere('expired_at', '>=', now());
        });
    }

    /**
     * Limit the query to ads assigned to a placement location.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForLocation(Builder $query, string $location): void
    {
        $query->where('location', $location);
    }

    /*
    |--------------------------------------------------------------------------
    | Domain state
    |--------------------------------------------------------------------------
    */

    /** Determine whether this ad is a Google AdSense unit. */
    public function isAdsense(): bool
    {
        return $this->ads_type === AdType::GoogleAdsense;
    }

    /** Determine whether this custom ad has passed its expiry date. */
    public function isExpired(): bool
    {
        if ($this->isAdsense()) {
            return false;
        }

        return $this->expired_at === null || $this->expired_at->lt(now());
    }

    /** Determine whether this ad should be shown publicly. */
    public function isDisplayable(): bool
    {
        return $this->status === AdStatus::Published && ! $this->isExpired();
    }

    /** Build the obfuscation hash used by the click-tracking route. */
    public function randomHash(): string
    {
        return hash('sha1', $this->key.$this->id);
    }

    /** Increment the click counter without firing model events or updating timestamps. */
    public function recordClick(): void
    {
        static::withoutEvents(
            fn () => static::withoutTimestamps(
                fn () => $this->increment('clicked'),
            ),
        );

        $this->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve the desktop/default creative URL.
     *
     * @return Attribute<covariant string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn (mixed $value, array $attributes): ?string => $this->resolveMediaUrl($this->image),
        );
    }

    /**
     * Resolve the tablet creative URL, falling back to the default image.
     *
     * @return Attribute<covariant string|null, never>
     */
    protected function tabletImageUrl(): Attribute
    {
        return Attribute::get(
            fn (mixed $value, array $attributes): ?string => $this->resolveMediaUrl($this->tablet_image ?: $this->image),
        );
    }

    /**
     * Resolve the mobile creative URL with tablet then desktop fallback.
     *
     * @return Attribute<covariant string|null, never>
     */
    protected function mobileImageUrl(): Attribute
    {
        return Attribute::get(
            fn (mixed $value, array $attributes): ?string => $this->resolveMediaUrl(($this->mobile_image ?: $this->tablet_image) ?: $this->image),
        );
    }

    /**
     * Build the obfuscated click-tracking URL for this ad.
     *
     * @return Attribute<covariant string|null, never>
     */
    protected function clickUrl(): Attribute
    {
        return Attribute::get(function (mixed $value, array $attributes): ?string {
            if (! $this->url) {
                return null;
            }

            return route('adment.click', [
                'randomHash' => $this->randomHash(),
                'adsKey' => $this->key,
            ]);
        });
    }

    /** Convert a stored media path into an absolute URL. */
    protected function resolveMediaUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk(config('adment.media.disk', 'public'))->url($path);
    }
}
