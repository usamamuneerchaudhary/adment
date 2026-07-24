<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Usamamuneerchaudhary\Adment\Contracts\ManagesAds;
use Usamamuneerchaudhary\Adment\Events\AdsLoading;
use Usamamuneerchaudhary\Adment\Facades\Ads;
use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;

beforeEach(function (): void {
    $this->manager = app(ManagesAds::class);
});

it('resolves the manager through the container and facade', function (): void {
    expect($this->manager)->toBeInstanceOf(ManagesAds::class)
        ->and(Ads::getFacadeRoot())->toBe($this->manager);
});

it('merges config locations with runtime registrations', function (): void {
    $this->manager->registerLocation('footer', 'Footer');

    expect($this->manager->getLocations())
        ->toHaveKeys(['not_set', 'sidebar', 'homepage-banner', 'footer']);
});

it('loads ads once and memoizes across calls', function (): void {
    Ad::factory()->count(3)->create();
    Event::fake([AdsLoading::class]);

    $this->manager->load();
    Ad::factory()->create();
    $this->manager->load(); // memoized — should not re-query

    expect($this->manager->getData())->toHaveCount(3);
    Event::assertDispatchedTimes(AdsLoading::class, 1);

    $this->manager->load(force: true);

    expect($this->manager->getData())->toHaveCount(4);
});

it('filters drafts and expired ads but keeps adsense units', function (): void {
    Ad::factory()->atLocation('sidebar')->create();
    Ad::factory()->atLocation('sidebar')->draft()->create();
    Ad::factory()->atLocation('sidebar')->expired()->create();
    Ad::factory()->atLocation('sidebar')->adsense()->create();

    $displayable = $this->manager->getData(load: true, displayableOnly: true);

    expect($displayable)->toHaveCount(2);
});

it('renders a single random ad for a location by default', function (): void {
    Ad::factory()->count(3)->atLocation('sidebar')->create();

    $html = $this->manager->display('sidebar');

    expect(substr_count($html, '<picture>'))->toBe(1);
});

it('renders all ads for a location ordered by weight when single is false', function (): void {
    Ad::factory()->atLocation('sidebar')->create(['order' => 2, 'image' => 'ads/second.jpg']);
    Ad::factory()->atLocation('sidebar')->create(['order' => 1, 'image' => 'ads/first.jpg']);

    $html = $this->manager->display('sidebar', single: false);

    expect(substr_count($html, '<picture>'))->toBe(2)
        ->and(strpos($html, 'first.jpg'))->toBeLessThan(strpos($html, 'second.jpg'));
});

it('returns an empty string for a location with no displayable ads', function (): void {
    Ad::factory()->atLocation('sidebar')->expired()->create();

    expect($this->manager->display('sidebar'))->toBe('');
});

it('renders a specific ad by key with a default centering style', function (): void {
    $ad = Ad::factory()->create();

    $html = $this->manager->displayAds($ad->key);

    expect($html)
        ->toContain('text-align: center;')
        ->toContain('<picture>');
});

it('honours caller-provided attributes over the default style', function (): void {
    $ad = Ad::factory()->create();

    $html = $this->manager->displayAds($ad->key, ['style' => 'margin: 0;', 'class' => 'banner']);

    expect($html)
        ->toContain('style="margin: 0;"')
        ->toContain('class="banner"')
        ->not->toContain('text-align: center;');
});

it('returns null for unknown, empty, or non-displayable keys', function (): void {
    $draft = Ad::factory()->draft()->create();

    expect($this->manager->displayAds('NOPE'))->toBeNull()
        ->and($this->manager->displayAds(null))->toBeNull()
        ->and($this->manager->displayAds(''))->toBeNull()
        ->and($this->manager->displayAds($draft->key))->toBeNull();
});

it('reports whether a location has displayable ads', function (): void {
    Ad::factory()->atLocation('sidebar')->expired()->create();
    Ad::factory()->atLocation('homepage-banner')->create();

    expect($this->manager->locationHasAds('sidebar'))->toBeFalse()
        ->and($this->manager->locationHasAds('homepage-banner'))->toBeTrue();
});

it('returns a model by key only when it has an image', function (): void {
    $withImage = Ad::factory()->create();
    $withoutImage = Ad::factory()->create(['image' => null]);

    expect($this->manager->getAd($withImage->key)?->id)->toBe($withImage->id)
        ->and($this->manager->getAd($withoutImage->key))->toBeNull();
});

it('renders adsense units with the configured client id', function (): void {
    app(AdsSettings::class)->set(AdsSettings::KEY_UNIT_CLIENT_ID, 'ca-pub-1234567890123456');

    $ad = Ad::factory()->adsense('9876543210')->atLocation('sidebar')->create();

    $html = app(ManagesAds::class)->display('sidebar');

    expect($html)
        ->toContain('class="adsbygoogle"')
        ->toContain('data-ad-client="ca-pub-1234567890123456"')
        ->toContain('data-ad-slot="9876543210"');
});

it('skips adsense units when no client id is configured', function (): void {
    Ad::factory()->adsense()->atLocation('sidebar')->create();

    $html = $this->manager->display('sidebar');

    expect($html)->not->toContain('adsbygoogle');
});

it('never leaks the raw destination URL into markup', function (): void {
    Ad::factory()->atLocation('sidebar')->create(['url' => 'https://secret-destination.test/offer']);

    $html = $this->manager->display('sidebar');

    expect($html)
        ->not->toContain('secret-destination.test')
        ->toContain('/ac-');
});

it('excludes scheduled ads from display', function (): void {
    Ad::factory()->atLocation('sidebar')->scheduled()->create();

    expect($this->manager->display('sidebar'))->toBe('');
});

it('excludes geo-targeted ads when the country header is missing', function (): void {
    Ad::factory()->atLocation('sidebar')->create([
        'target_countries' => ['US'],
        'image' => 'ads/geo.jpg',
    ]);

    expect($this->manager->display('sidebar'))->toBe('');
});

it('includes geo-targeted ads when the country header matches', function (): void {
    Ad::factory()->atLocation('sidebar')->create([
        'target_countries' => ['US'],
        'image' => 'ads/geo.jpg',
    ]);

    request()->headers->set('CF-IPCountry', 'US');

    $html = $this->manager->display('sidebar');

    expect($html)->toContain('ads/geo.jpg');
});

it('includes impression beacon attributes for custom ads', function (): void {
    $ad = Ad::factory()->atLocation('sidebar')->create();

    $html = $this->manager->display('sidebar');

    expect($html)
        ->toContain('data-adment-impression=')
        ->toContain('data-adment-key="'.$ad->key.'"')
        ->toContain('__admentImpressionBeacon');
});
