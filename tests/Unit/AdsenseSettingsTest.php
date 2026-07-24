<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Usamamuneerchaudhary\Adment\Enums\AdsenseMode;
use Usamamuneerchaudhary\Adment\Rules\ValidAdsenseAutoSnippet;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;

function validateSnippet(string $snippet): bool
{
    return Validator::make(
        ['snippet' => $snippet],
        ['snippet' => [new ValidAdsenseAutoSnippet]],
    )->passes();
}

const VALID_SNIPPET = '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456" crossorigin="anonymous"></script>';

it('accepts the official auto ads snippet', function (): void {
    expect(validateSnippet(VALID_SNIPPET))->toBeTrue();
});

it('rejects malicious or malformed snippets', function (string $snippet): void {
    expect(validateSnippet($snippet))->toBeFalse();
})->with([
    'inline JS' => ['<script>alert(1)</script>'],
    'eval' => ['<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456" crossorigin="anonymous">eval(x)</script>'],
    'document.write' => ['<script async crossorigin="anonymous" src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456">document.write("x")</script>'],
    'wrong host' => ['<script async src="https://evil.example.com/adsbygoogle.js?client=ca-pub-1234567890123456" crossorigin="anonymous"></script>'],
    'short client id' => ['<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-123" crossorigin="anonymous"></script>'],
    'missing async' => ['<script src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456" crossorigin="anonymous"></script>'],
    'missing crossorigin' => ['<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456"></script>'],
    'two script tags' => [VALID_SNIPPET.'<script>x</script>'],
    'data url' => ['<script async src="data:text/javascript;base64,YWxlcnQoMSk=" crossorigin="anonymous"></script>'],
    'http scheme' => ['<script async src="http://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456" crossorigin="anonymous"></script>'],
]);

it('rejects snippets longer than 1000 characters', function (): void {
    expect(validateSnippet(str_repeat('a', 1001)))->toBeFalse();
});

it('persists and reads settings through the cached store', function (): void {
    $settings = app(AdsSettings::class);

    $settings->updateAdsense(AdsenseMode::Unit, unitClientId: 'ca-pub-1234567890123456');

    expect($settings->mode())->toBe(AdsenseMode::Unit)
        ->and($settings->unitClientId())->toBe('ca-pub-1234567890123456')
        ->and($settings->autoAdsSnippet())->toBeNull();
});

it('clears mode-specific values when switching modes', function (): void {
    $settings = app(AdsSettings::class);

    $settings->updateAdsense(AdsenseMode::Auto, autoSnippet: VALID_SNIPPET);
    $settings->updateAdsense(AdsenseMode::None);

    expect($settings->mode())->toBe(AdsenseMode::None)
        ->and($settings->autoAdsSnippet())->toBeNull()
        ->and($settings->unitClientId())->toBeNull();
});

it('defaults to none when nothing is stored', function (): void {
    expect(app(AdsSettings::class)->mode())->toBe(AdsenseMode::None);
});
