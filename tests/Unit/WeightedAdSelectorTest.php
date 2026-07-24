<?php

declare(strict_types=1);

use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Support\WeightedAdSelector;

it('returns null for an empty collection', function (): void {
    expect((new WeightedAdSelector)->select(collect()))->toBeNull();
});

it('returns the only ad when the collection has one item', function (): void {
    $ad = Ad::factory()->make(['order' => 5]);

    expect((new WeightedAdSelector)->select(collect([$ad])))->toBe($ad);
});

it('normalizes zero and negative order values to a weight of one', function (): void {
    $selector = new WeightedAdSelector;
    $ad = Ad::factory()->make(['order' => 0]);

    expect($selector->weight($ad))->toBe(1);
});

it('favours higher-weighted ads over many selections', function (): void {
    $heavy = Ad::factory()->create(['order' => 90, 'location' => 'sidebar']);
    $light = Ad::factory()->create(['order' => 10, 'location' => 'sidebar']);

    $selector = new WeightedAdSelector;
    $ads = collect([$heavy, $light]);

    $counts = [$heavy->id => 0, $light->id => 0];

    for ($i = 0; $i < 200; $i++) {
        $selected = $selector->select($ads);
        $counts[$selected->id]++;
    }

    expect($counts[$heavy->id])->toBeGreaterThan($counts[$light->id]);
});
