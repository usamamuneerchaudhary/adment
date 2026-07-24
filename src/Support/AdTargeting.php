<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Support;

use Illuminate\Http\Request;
use Usamamuneerchaudhary\Adment\Enums\AdDevice;
use Usamamuneerchaudhary\Adment\Models\Ad;

class AdTargeting
{
    /** Resolve the visitor country from CDN headers or a configured resolver. */
    public function resolveCountry(?Request $request = null): ?string
    {
        $request ??= request();

        $resolver = config('adment.targeting.country_resolver');

        if (is_callable($resolver)) {
            $resolved = $resolver($request);

            if (is_string($resolved) && $resolved !== '') {
                return strtoupper($resolved);
            }
        }

        foreach (['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Country-Code'] as $header) {
            $value = $request->headers->get($header);

            if (is_string($value) && strlen($value) === 2 && strtoupper($value) !== 'XX') {
                return strtoupper($value);
            }
        }

        return null;
    }

    /** Resolve the visitor device class from the User-Agent string. */
    public function resolveDevice(?Request $request = null): AdDevice
    {
        $request ??= request();
        $userAgent = strtolower((string) $request->userAgent());

        if ($userAgent === '') {
            return AdDevice::Desktop;
        }

        if (preg_match('/ipad|tablet|kindle|playbook|silk|(android(?!.*mobile))/i', $userAgent) === 1) {
            return AdDevice::Tablet;
        }

        if (preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|opera mobi|windows phone/i', $userAgent) === 1) {
            return AdDevice::Mobile;
        }

        return AdDevice::Desktop;
    }

    /** Determine whether the ad matches the current request's country and device. */
    public function matches(Ad $ad, ?Request $request = null): bool
    {
        $request ??= request();

        if (! $this->matchesCountry($ad, $request)) {
            return false;
        }

        return $this->matchesDevice($ad, $request);
    }

    /** Check country targeting rules for the ad. */
    protected function matchesCountry(Ad $ad, Request $request): bool
    {
        $countries = array_values(array_filter(
            array_map(
                static fn (mixed $code): string => strtoupper((string) $code),
                $ad->target_countries ?? [],
            ),
            static fn (string $code): bool => $code !== '',
        ));

        if ($countries === []) {
            return true;
        }

        $resolved = $this->resolveCountry($request);

        if ($resolved === null) {
            return config('adment.targeting.unknown_country_behavior', 'exclude_restricted') !== 'exclude_restricted';
        }

        return in_array($resolved, $countries, true);
    }

    /** Check device targeting rules for the ad. */
    protected function matchesDevice(Ad $ad, Request $request): bool
    {
        $devices = array_values(array_filter(
            array_map(
                static fn (mixed $device): string => strtolower((string) $device),
                $ad->target_devices ?? [],
            ),
            static fn (string $device): bool => $device !== '',
        ));

        if ($devices === []) {
            return true;
        }

        return in_array($this->resolveDevice($request)->value, $devices, true);
    }
}
