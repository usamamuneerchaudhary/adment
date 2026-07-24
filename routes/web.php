<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Usamamuneerchaudhary\Adment\Http\Controllers\AdClickController;
use Usamamuneerchaudhary\Adment\Http\Controllers\AdImpressionController;

$clickPrefix = config('adment.routes.click_prefix', 'ac');
$impressionPrefix = config('adment.routes.impression_prefix', 'ai');

Route::middleware(config('adment.routes.middleware', ['web']))->group(function () use ($clickPrefix, $impressionPrefix): void {
    Route::get($clickPrefix.'-{randomHash}/{adsKey}', AdClickController::class)
        ->where('randomHash', '[a-f0-9]{40}')
        ->where('adsKey', '[A-Za-z0-9\-_]+')
        ->name('adment.click');

    Route::post($impressionPrefix.'-{randomHash}/{adsKey}', AdImpressionController::class)
        ->where('randomHash', '[a-f0-9]{40}')
        ->where('adsKey', '[A-Za-z0-9\-_]+')
        ->name('adment.impression');

    Route::get('ads-click/{key}', [AdClickController::class, 'legacy'])
        ->where('key', '[A-Za-z0-9\-_]+')
        ->name('adment.click.legacy');
});
