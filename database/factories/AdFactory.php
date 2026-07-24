<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Usamamuneerchaudhary\Adment\Enums\AdMediaType;
use Usamamuneerchaudhary\Adment\Enums\AdStatus;
use Usamamuneerchaudhary\Adment\Enums\AdType;
use Usamamuneerchaudhary\Adment\Models\Ad;

/**
 * @extends Factory<Ad>
 */
class AdFactory extends Factory
{
    protected $model = Ad::class;

    /** Define the default state for a custom published ad. */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'key' => Str::upper(Str::random(12)),
            'location' => 'not_set',
            'image' => 'ads/'.$this->faker->uuid().'.jpg',
            'tablet_image' => null,
            'mobile_image' => null,
            'media_type' => AdMediaType::Image,
            'url' => $this->faker->url(),
            'open_in_new_tab' => true,
            'starts_at' => null,
            'expired_at' => now()->addMonth(),
            'order' => $this->faker->numberBetween(0, 10),
            'status' => AdStatus::Published,
            'ads_type' => AdType::Custom,
            'google_adsense_slot_id' => null,
            'target_countries' => null,
            'target_devices' => null,
        ];
    }

    /** Resolve the configured ad model class for this factory. */
    public function modelName(): string
    {
        return config('adment.models.ad', Ad::class);
    }

    /** Mark the ad as a draft. */
    public function draft(): static
    {
        return $this->state(['status' => AdStatus::Draft]);
    }

    /** Mark the ad as already expired. */
    public function expired(): static
    {
        return $this->state(['expired_at' => now()->subDay()]);
    }

    /** Convert the ad into a Google AdSense unit. */
    public function adsense(?string $slotId = null): static
    {
        return $this->state([
            'ads_type' => AdType::GoogleAdsense,
            'google_adsense_slot_id' => $slotId ?? (string) $this->faker->numberBetween(1000000000, 9999999999),
            'image' => null,
            'url' => null,
            'expired_at' => null,
        ]);
    }

    /** Assign the ad to a specific placement location. */
    public function atLocation(string $location): static
    {
        return $this->state(['location' => $location]);
    }

    /** Schedule the ad to start in the future. */
    public function scheduled(): static
    {
        return $this->state(['starts_at' => now()->addDay()]);
    }

    /** Mark the ad as a GIF creative. */
    public function gif(): static
    {
        return $this->state([
            'media_type' => AdMediaType::Gif,
            'image' => 'ads/'.$this->faker->uuid().'.gif',
        ]);
    }

    /** Mark the ad as a video creative. */
    public function video(): static
    {
        return $this->state([
            'media_type' => AdMediaType::Video,
            'image' => 'ads/'.$this->faker->uuid().'.mp4',
        ]);
    }
}
