<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Usamamuneerchaudhary\Adment\Models\Ad;

class AdController
{
    /** Return displayable ads as JSON, optionally filtered by public keys. */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keys' => ['sometimes', 'array', 'max:50'],
            'keys.*' => ['string', 'max:120'],
        ]);

        /** @var class-string<Ad> $model */
        $model = config('adment.models.ad', Ad::class);

        /** @var list<string>|null $keys */
        $keys = $validated['keys'] ?? null;

        $ads = $model::query()
            ->displayable()
            ->when($keys, fn ($query, array $keys) => $query->whereIn('key', $keys))
            ->orderBy('order')
            ->get()
            ->map(fn (Ad $ad): array => [
                'key' => $ad->key,
                'name' => $ad->name,
                'image' => $ad->image_url,
                'tablet_image' => $ad->tablet_image_url,
                'mobile_image' => $ad->mobile_image_url,
                'link' => $ad->click_url,
                'order' => $ad->order,
                'open_in_new_tab' => $ad->open_in_new_tab,
                'ads_type' => $ad->ads_type->value,
                'google_adsense_slot_id' => $ad->google_adsense_slot_id,
            ]);

        return response()->json([
            'error' => false,
            'data' => $ads,
        ]);
    }
}
