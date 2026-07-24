<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Facades;

use Illuminate\Support\Facades\Facade;
use Usamamuneerchaudhary\Adment\Contracts\ManagesAds;
use Usamamuneerchaudhary\Adment\Support\AdsManager;

/**
 * @method static ManagesAds registerLocation(string $key, string $name)
 * @method static array<string, string> getLocations()
 * @method static ManagesAds load(bool $force = false)
 * @method static string display(string $location, array<string, mixed> $attributes = [], bool $single = true)
 * @method static string|null displayAds(?string $key, array<string, mixed> $attributes = [])
 * @method static bool locationHasAds(string $location)
 * @method static \Usamamuneerchaudhary\Adment\Models\Ad|null getAd(string $key)
 * @method static \Illuminate\Support\Collection<int, \Usamamuneerchaudhary\Adment\Models\Ad> getData(bool $load = false, bool $displayableOnly = false)
 *
 * @see AdsManager
 */
class Ads extends Facade
{
    /** Resolve the ManagesAds contract as the facade accessor. */
    protected static function getFacadeAccessor(): string
    {
        return ManagesAds::class;
    }
}
