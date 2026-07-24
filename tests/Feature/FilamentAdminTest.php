<?php

declare(strict_types=1);

use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

use Usamamuneerchaudhary\Adment\Enums\AdsenseMode;
use Usamamuneerchaudhary\Adment\Enums\AdStatus;
use Usamamuneerchaudhary\Adment\Enums\AdType;
use Usamamuneerchaudhary\Adment\Filament\Pages\ManageAdsSettings;
use Usamamuneerchaudhary\Adment\Filament\Resources\AdResource\Pages\CreateAd;
use Usamamuneerchaudhary\Adment\Filament\Resources\AdResource\Pages\EditAd;
use Usamamuneerchaudhary\Adment\Filament\Resources\AdResource\Pages\ListAds;
use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;
use Usamamuneerchaudhary\Adment\Tests\Fixtures\User;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(User::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('secret'),
    ]));
});

it('lists ads on the index page', function (): void {
    $ads = Ad::factory()->count(3)->create();

    livewire(ListAds::class)
        ->assertOk()
        ->assertCanSeeTableRecords($ads);
});

it('creates a custom ad with an auto-generated key', function (): void {
    livewire(CreateAd::class)
        ->fillForm([
            'name' => 'Homepage banner',
            'ads_type' => AdType::Custom->value,
            'status' => AdStatus::Published->value,
            'location' => 'homepage-banner',
            'order' => 1,
            'expired_at' => now()->addMonth()->toDateTimeString(),
            'url' => 'https://example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ad = Ad::query()->firstWhere('name', 'Homepage banner');

    expect($ad)->not->toBeNull()
        ->and($ad->key)->toHaveLength(12)
        ->and($ad->location)->toBe('homepage-banner');
});

it('requires an expiry date for custom ads', function (): void {
    livewire(CreateAd::class)
        ->fillForm([
            'name' => 'No expiry',
            'ads_type' => AdType::Custom->value,
            'status' => AdStatus::Published->value,
            'order' => 0,
            'expired_at' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['expired_at' => 'required']);
});

it('requires a slot id for adsense units but not an expiry date', function (): void {
    livewire(CreateAd::class)
        ->fillForm([
            'name' => 'AdSense unit',
            'ads_type' => AdType::GoogleAdsense->value,
            'status' => AdStatus::Published->value,
            'order' => 0,
            'google_adsense_slot_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['google_adsense_slot_id' => 'required']);

    livewire(CreateAd::class)
        ->fillForm([
            'name' => 'AdSense unit',
            'ads_type' => AdType::GoogleAdsense->value,
            'status' => AdStatus::Published->value,
            'order' => 0,
            'google_adsense_slot_id' => '1234567890',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('rejects duplicate keys and out-of-range order values', function (): void {
    Ad::factory()->create(['key' => 'TAKENKEY0000']);

    livewire(CreateAd::class)
        ->fillForm([
            'name' => 'Duplicate',
            'key' => 'TAKENKEY0000',
            'ads_type' => AdType::Custom->value,
            'status' => AdStatus::Published->value,
            'order' => 999,
            'expired_at' => now()->addMonth()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['key' => 'unique', 'order' => 'max']);
});

it('edits an existing ad', function (): void {
    $ad = Ad::factory()->create(['name' => 'Old name']);

    livewire(EditAd::class, ['record' => $ad->getRouteKey()])
        ->fillForm(['name' => 'New name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($ad->fresh()->name)->toBe('New name');
});

it('saves adsense settings in unit mode', function (): void {
    livewire(ManageAdsSettings::class)
        ->fillForm([
            'mode' => AdsenseMode::Unit->value,
            'unit_client_id' => 'ca-pub-1234567890123456',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(AdsSettings::class);
    $settings->flushCache();

    expect($settings->mode())->toBe(AdsenseMode::Unit)
        ->and($settings->unitClientId())->toBe('ca-pub-1234567890123456');
});

it('rejects an invalid client id', function (): void {
    livewire(ManageAdsSettings::class)
        ->fillForm([
            'mode' => AdsenseMode::Unit->value,
            'unit_client_id' => 'ca-pub-123',
        ])
        ->call('save')
        ->assertHasFormErrors(['unit_client_id']);
});

it('rejects a tampered auto ads snippet', function (): void {
    livewire(ManageAdsSettings::class)
        ->fillForm([
            'mode' => AdsenseMode::Auto->value,
            'auto_ads_snippet' => '<script>alert(1)</script>',
        ])
        ->call('save')
        ->assertHasFormErrors(['auto_ads_snippet']);
});
