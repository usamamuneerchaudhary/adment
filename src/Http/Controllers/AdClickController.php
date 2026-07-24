<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Usamamuneerchaudhary\Adment\Events\AdClicked;
use Usamamuneerchaudhary\Adment\Models\Ad;

class AdClickController
{
    /** Handle the obfuscated click route and redirect to the destination URL. */
    public function __invoke(Request $request, string $randomHash, string $adsKey): RedirectResponse
    {
        $ad = $this->findAd($adsKey);

        if (! $ad instanceof Ad || ! hash_equals($ad->randomHash(), $randomHash)) {
            return $this->fallback();
        }

        return $this->trackAndRedirect($request, $ad);
    }

    /** Handle the legacy click route that identifies ads by public key only. */
    public function legacy(Request $request, string $key): RedirectResponse
    {
        $ad = $this->findAd($key);

        if (! $ad instanceof Ad) {
            return $this->fallback();
        }

        return $this->trackAndRedirect($request, $ad);
    }

    /** Record a click and redirect away to a safe destination URL. */
    protected function trackAndRedirect(Request $request, Ad $ad): RedirectResponse
    {
        if (! $this->isSafeUrl($ad->url)) {
            return $this->fallback();
        }

        $ad->recordClick();

        AdClicked::dispatch($ad, $request->headers->get('referer'), $request->userAgent());

        return redirect()->away($ad->url);
    }

    /** Look up an ad by its public key. */
    protected function findAd(string $key): ?Ad
    {
        /** @var class-string<Ad> $model */
        $model = config('adment.models.ad', Ad::class);

        return $model::query()->where('key', $key)->first();
    }

    /** Determine whether a destination URL uses a safe http(s) scheme. */
    protected function isSafeUrl(?string $url): bool
    {
        return is_string($url) && Str::startsWith($url, ['http://', 'https://']);
    }

    /** Redirect to the configured fallback path when tracking fails. */
    protected function fallback(): RedirectResponse
    {
        return redirect(config('adment.routes.fallback_redirect', '/'));
    }
}
