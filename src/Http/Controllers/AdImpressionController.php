<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Usamamuneerchaudhary\Adment\Events\AdImpressionRecorded;
use Usamamuneerchaudhary\Adment\Models\Ad;

class AdImpressionController
{
    /** Handle the obfuscated impression beacon and return an empty success response. */
    public function __invoke(Request $request, string $randomHash, string $adsKey): Response
    {
        $ad = $this->findAd($adsKey);

        if (! $ad instanceof Ad || ! hash_equals($ad->randomHash(), $randomHash)) {
            return response()->noContent(404);
        }

        if ($ad->isAdsense() || ! $ad->hasCreative()) {
            return response()->noContent(404);
        }

        $ad->recordImpression();

        AdImpressionRecorded::dispatch($ad, $request->headers->get('referer'), $request->userAgent());

        return response()->noContent();
    }

    /** Look up an ad by its public key. */
    protected function findAd(string $key): ?Ad
    {
        /** @var class-string<Ad> $model */
        $model = config('adment.models.ad', Ad::class);

        return $model::query()->where('key', $key)->first();
    }
}
