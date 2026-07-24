<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Facades;

use Illuminate\Support\Facades\Facade;
use Usamamuneerchaudhary\Adment\Contracts\ManagesAds;
use Usamamuneerchaudhary\Adment\Support\AdsManager;

/**
 * @method static \Usamamuneerchaudhary\Adment\Contracts\ManagesAds registerLocation(string $key, string $name)
 * @method static array getLocations()
 * @method static \Usamamuneerchaudhary\Adment\Contracts\ManagesAds load(bool $force = false)
 * @method static string display(string $location, array $attributes = [], bool $single = true)
 * @method static string|null displayAds(?string $key, array $attributes = [])
 * @method static bool locationHasAds(string $location)
 * @method static \Usamamuneerchaudhary\Adment\Models\Ad|null getAd(string $key)
 * @method static \Illuminate\Support\Collection getData(bool $load = false, bool $displayableOnly = false)
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
