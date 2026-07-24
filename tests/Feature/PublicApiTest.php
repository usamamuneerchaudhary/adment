<?php

declare(strict_types=1);

use Usamamuneerchaudhary\Adment\Models\Ad;

beforeEach(function (): void {
    config()->set('adment.api.enabled', true);

    // Routes are registered at boot; re-register after flipping the flag.
    require __DIR__.'/../../routes/api.php';
});

it('returns only displayable ads ordered by weight', function (): void {
    Ad::factory()->create(['order' => 5, 'key' => 'SECOND000000']);
    Ad::factory()->create(['order' => 1, 'key' => 'FIRST0000000']);
    Ad::factory()->draft()->create();
    Ad::factory()->expired()->create();

    $response = $this->getJson('/api/v1/ads');

    $response->assertOk()
        ->assertJsonPath('error', false)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.key', 'FIRST0000000')
        ->assertJsonPath('data.1.key', 'SECOND000000');
});

it('filters by keys', function (): void {
    Ad::factory()->create(['key' => 'WANTED000000']);
    Ad::factory()->create(['key' => 'UNWANTED0000']);

    $this->getJson('/api/v1/ads?keys[]=WANTED000000')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.key', 'WANTED000000');
});

it('exposes the tracked click URL instead of the raw destination', function (): void {
    Ad::factory()->create(['key' => 'TRACKED00000', 'url' => 'https://example.com/promo']);

    $response = $this->getJson('/api/v1/ads?keys[]=TRACKED00000');

    $link = $response->json('data.0.link');

    expect($link)->toContain('/ac-')
        ->not->toContain('example.com/promo');
});

it('validates the keys payload', function (): void {
    $this->getJson('/api/v1/ads?keys[]='.str_repeat('X', 200))
        ->assertStatus(422);
});
