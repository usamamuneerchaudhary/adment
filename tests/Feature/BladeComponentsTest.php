<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Usamamuneerchaudhary\Adment\Enums\AdsenseMode;
use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;

it('renders an ad by location through the blade component', function (): void {
    Ad::factory()->atLocation('sidebar')->create(['image' => 'ads/banner.jpg']);

    $html = Blade::render('<x-adment::display location="sidebar" />');

    expect($html)
        ->toContain('<picture>')
        ->toContain('ads/banner.jpg');
});

it('renders an ad by key through the blade component', function (): void {
    $ad = Ad::factory()->create(['image' => 'ads/keyed.jpg']);

    $html = Blade::render('<x-adment::display :ad-key="$key" />', ['key' => $ad->key]);

    expect($html)->toContain('ads/keyed.jpg');
});

it('renders nothing for an empty location', function (): void {
    $html = Blade::render('<x-adment::display location="sidebar" />');

    expect(trim($html))->toBe('');
});

it('injects the auto ads snippet in head when mode is auto', function (): void {
    $snippet = '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456" crossorigin="anonymous"></script>';
    app(AdsSettings::class)->updateAdsense(AdsenseMode::Auto, autoSnippet: $snippet);

    $html = Blade::render('<x-adment::adsense-head />');

    expect($html)->toContain('ca-pub-1234567890123456');
});

it('injects the unit loader in head and push script in foot when mode is unit', function (): void {
    app(AdsSettings::class)->updateAdsense(AdsenseMode::Unit, unitClientId: 'ca-pub-1234567890123456');

    $head = Blade::render('<x-adment::adsense-head />');
    $foot = Blade::render('<x-adment::adsense-foot />');

    expect($head)->toContain('adsbygoogle.js?client=ca-pub-1234567890123456')
        ->and($foot)->toContain('adsbygoogle = window.adsbygoogle || []');
});

it('injects nothing when mode is none', function (): void {
    $head = Blade::render('<x-adment::adsense-head />');
    $foot = Blade::render('<x-adment::adsense-foot />');

    expect(trim($head))->toBe('')
        ->and(trim($foot))->toBe('');
});

it('renders gif creatives as an img tag', function (): void {
    Ad::factory()->gif()->atLocation('sidebar')->create(['image' => 'ads/banner.gif']);

    $html = Blade::render('<x-adment::display location="sidebar" />');

    expect($html)
        ->toContain('<img')
        ->toContain('ads/banner.gif')
        ->not->toContain('<picture>');
});

it('renders video creatives as a video tag', function (): void {
    Ad::factory()->video()->atLocation('sidebar')->create(['image' => 'ads/banner.mp4']);

    $html = Blade::render('<x-adment::display location="sidebar" />');

    expect($html)
        ->toContain('<video')
        ->toContain('ads/banner.mp4')
        ->not->toContain('<picture>');
});
