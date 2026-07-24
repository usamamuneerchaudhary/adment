<?php

declare(strict_types=1);

use Usamamuneerchaudhary\Adment\Enums\AdStatus;
use Usamamuneerchaudhary\Adment\Models\Ad;

it('auto-generates a unique 12-character uppercase key on create', function (): void {
    $ad = Ad::factory()->create(['key' => null]);

    expect($ad->key)
        ->toHaveLength(12)
        ->toBe(strtoupper($ad->key));
});

it('keeps an explicitly provided key', function (): void {
    $ad = Ad::factory()->create(['key' => 'MYCUSTOMKEY1']);

    expect($ad->key)->toBe('MYCUSTOMKEY1');
});

it('never generates a colliding key', function (): void {
    Ad::factory()->count(25)->create(['key' => null]);

    expect(Ad::query()->pluck('key')->unique())->toHaveCount(25);
});

it('computes the obfuscation hash as sha1(key . id)', function (): void {
    $ad = Ad::factory()->create();

    expect($ad->randomHash())->toBe(hash('sha1', $ad->key.$ad->id));
});

it('falls back tablet and mobile image URLs to the default image', function (): void {
    $ad = Ad::factory()->create([
        'image' => 'ads/desktop.jpg',
        'tablet_image' => null,
        'mobile_image' => null,
    ]);

    expect($ad->tablet_image_url)->toContain('ads/desktop.jpg')
        ->and($ad->mobile_image_url)->toContain('ads/desktop.jpg');
});

it('falls back mobile image to tablet image before default', function (): void {
    $ad = Ad::factory()->create([
        'image' => 'ads/desktop.jpg',
        'tablet_image' => 'ads/tablet.jpg',
        'mobile_image' => null,
    ]);

    expect($ad->mobile_image_url)->toContain('ads/tablet.jpg');
});

it('passes absolute image URLs through untouched', function (): void {
    $ad = Ad::factory()->create(['image' => 'https://cdn.example.com/banner.jpg']);

    expect($ad->image_url)->toBe('https://cdn.example.com/banner.jpg');
});

it('builds the click URL through the tracking route, never the raw destination', function (): void {
    $ad = Ad::factory()->create(['url' => 'https://example.com/landing']);

    expect($ad->click_url)
        ->toContain('ac-'.$ad->randomHash())
        ->toContain($ad->key)
        ->not->toContain('example.com/landing');
});

it('has no click URL when the ad has no destination', function (): void {
    $ad = Ad::factory()->create(['url' => null]);

    expect($ad->click_url)->toBeNull();
});

it('treats adsense units as never expired', function (): void {
    $ad = Ad::factory()->adsense()->create(['expired_at' => null]);

    expect($ad->isExpired())->toBeFalse()
        ->and($ad->isDisplayable())->toBeTrue();
});

it('marks past-dated custom ads as expired', function (): void {
    $ad = Ad::factory()->expired()->create();

    expect($ad->isExpired())->toBeTrue()
        ->and($ad->isDisplayable())->toBeFalse();
});

it('excludes drafts from the displayable scope', function (): void {
    Ad::factory()->create();
    Ad::factory()->draft()->create();
    Ad::factory()->expired()->create();
    Ad::factory()->adsense()->draft()->create();
    $adsense = Ad::factory()->adsense()->create();

    $displayable = Ad::query()->displayable()->get();

    expect($displayable)->toHaveCount(2)
        ->and($displayable->pluck('id'))->toContain($adsense->id);
});

it('increments clicks without touching updated_at', function (): void {
    $ad = Ad::factory()->create();
    $originalUpdatedAt = $ad->updated_at;

    $this->travel(5)->minutes();
    $ad->recordClick();

    expect($ad->clicked)->toBe(1)
        ->and($ad->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});

it('casts status and type to enums', function (): void {
    $ad = Ad::factory()->create();

    expect($ad->status)->toBeInstanceOf(AdStatus::class);
});
