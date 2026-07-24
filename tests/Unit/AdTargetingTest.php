<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Usamamuneerchaudhary\Adment\Enums\AdDevice;
use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Support\AdTargeting;

it('treats empty country and device targeting as a match for everyone', function (): void {
    $ad = Ad::factory()->make([
        'target_countries' => null,
        'target_devices' => null,
    ]);

    $request = Request::create('/', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)');

    expect((new AdTargeting)->matches($ad, $request))->toBeTrue();
});

it('resolves country from CDN headers', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('CF-IPCountry', 'gb');

    expect((new AdTargeting)->resolveCountry($request))->toBe('GB');
});

it('resolves mobile devices from the user agent', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15');

    expect((new AdTargeting)->resolveDevice($request))->toBe(AdDevice::Mobile);
});

it('resolves tablet devices from the user agent', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15');

    expect((new AdTargeting)->resolveDevice($request))->toBe(AdDevice::Tablet);
});

it('excludes geo-targeted ads when the country is unknown', function (): void {
    $ad = Ad::factory()->make([
        'target_countries' => ['US'],
        'target_devices' => null,
    ]);

    $request = Request::create('/', 'GET');

    expect((new AdTargeting)->matches($ad, $request))->toBeFalse();
});

it('matches when the resolved country is in the target list', function (): void {
    $ad = Ad::factory()->make([
        'target_countries' => ['us', 'ca'],
        'target_devices' => null,
    ]);

    $request = Request::create('/', 'GET');
    $request->headers->set('CF-IPCountry', 'US');

    expect((new AdTargeting)->matches($ad, $request))->toBeTrue();
});

it('excludes ads that target a different device', function (): void {
    $ad = Ad::factory()->make([
        'target_countries' => null,
        'target_devices' => [AdDevice::Desktop->value],
    ]);

    $request = Request::create('/', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)');

    expect((new AdTargeting)->matches($ad, $request))->toBeFalse();
});

it('uses a configured country resolver callback when provided', function (): void {
    config()->set('adment.targeting.country_resolver', fn (): string => 'DE');

    $request = Request::create('/', 'GET');

    expect((new AdTargeting)->resolveCountry($request))->toBe('DE');
});
