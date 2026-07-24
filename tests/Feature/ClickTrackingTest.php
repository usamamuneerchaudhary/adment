<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Usamamuneerchaudhary\Adment\Events\AdClicked;
use Usamamuneerchaudhary\Adment\Models\Ad;

it('increments the click counter and redirects to the destination', function (): void {
    Event::fake([AdClicked::class]);
    $ad = Ad::factory()->create(['url' => 'https://example.com/landing']);

    $response = $this->get("/ac-{$ad->randomHash()}/{$ad->key}");

    $response->assertRedirect('https://example.com/landing');
    expect($ad->fresh()->clicked)->toBe(1);
    Event::assertDispatched(AdClicked::class, fn (AdClicked $event): bool => $event->ad->is($ad));
});

it('rejects a tampered hash without counting a click', function (): void {
    $ad = Ad::factory()->create(['url' => 'https://example.com/landing']);
    $wrongHash = hash('sha1', 'tampered');

    $response = $this->get("/ac-{$wrongHash}/{$ad->key}");

    $response->assertRedirect(config('adment.routes.fallback_redirect'));
    expect($ad->fresh()->clicked)->toBe(0);
});

it('falls back gracefully for unknown keys', function (): void {
    $hash = hash('sha1', 'whatever');

    $this->get("/ac-{$hash}/UNKNOWNKEY99")
        ->assertRedirect(config('adment.routes.fallback_redirect'));
});

it('falls back when the ad has no destination URL', function (): void {
    $ad = Ad::factory()->create(['url' => null]);

    $this->get("/ac-{$ad->randomHash()}/{$ad->key}")
        ->assertRedirect(config('adment.routes.fallback_redirect'));

    expect($ad->fresh()->clicked)->toBe(0);
});

it('refuses to redirect to non-http destinations', function (): void {
    $ad = Ad::factory()->create();
    $ad->forceFill(['url' => 'javascript:alert(1)'])->save();

    $this->get("/ac-{$ad->randomHash()}/{$ad->key}")
        ->assertRedirect(config('adment.routes.fallback_redirect'));

    expect($ad->fresh()->clicked)->toBe(0);
});

it('supports the legacy click route', function (): void {
    $ad = Ad::factory()->create(['url' => 'https://example.com/landing']);

    $this->get("/ads-click/{$ad->key}")
        ->assertRedirect('https://example.com/landing');

    expect($ad->fresh()->clicked)->toBe(1);
});

it('does not bump updated_at when recording clicks', function (): void {
    $ad = Ad::factory()->create(['url' => 'https://example.com/landing']);
    $before = $ad->updated_at;

    $this->travel(1)->hour();
    $this->get("/ac-{$ad->randomHash()}/{$ad->key}");

    expect($ad->fresh()->updated_at->equalTo($before))->toBeTrue();
});
