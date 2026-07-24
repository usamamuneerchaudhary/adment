<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Usamamuneerchaudhary\Adment\Events\AdImpressionRecorded;
use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Models\AdDailyStat;

it('increments impressions and daily stats via the beacon endpoint', function (): void {
    Event::fake([AdImpressionRecorded::class]);
    $ad = Ad::factory()->create();

    $this->post("/ai-{$ad->randomHash()}/{$ad->key}")
        ->assertNoContent();

    $ad->refresh();

    expect($ad->impressions)->toBe(1);

    $stat = AdDailyStat::query()->where('ad_id', $ad->id)->whereDate('date', now())->first();

    expect($stat)->not->toBeNull()
        ->and($stat->impressions)->toBe(1)
        ->and($stat->clicks)->toBe(0);

    Event::assertDispatched(AdImpressionRecorded::class, fn (AdImpressionRecorded $event): bool => $event->ad->is($ad));
});

it('rejects a tampered impression hash without counting', function (): void {
    $ad = Ad::factory()->create();
    $wrongHash = hash('sha1', 'tampered');

    $this->post("/ai-{$wrongHash}/{$ad->key}")
        ->assertNotFound();

    expect($ad->fresh()->impressions)->toBe(0);
});

it('does not bump updated_at when recording impressions', function (): void {
    $ad = Ad::factory()->create();
    $before = $ad->updated_at;

    $this->travel(1)->hour();
    $this->post("/ai-{$ad->randomHash()}/{$ad->key}")
        ->assertNoContent();

    expect($ad->fresh()->updated_at->equalTo($before))->toBeTrue();
});

it('records daily clicks when a click is tracked', function (): void {
    $ad = Ad::factory()->create(['url' => 'https://example.com/landing']);

    $this->get("/ac-{$ad->randomHash()}/{$ad->key}")
        ->assertRedirect('https://example.com/landing');

    $stat = AdDailyStat::query()->where('ad_id', $ad->id)->whereDate('date', now())->first();

    expect($ad->fresh()->clicked)->toBe(1)
        ->and($stat)->not->toBeNull()
        ->and($stat->clicks)->toBe(1);
});
