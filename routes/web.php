<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Usamamuneerchaudhary\Adment\Http\Controllers\AdClickController;

$prefix = config('adment.routes.click_prefix', 'ac');

Route::middleware(config('adment.routes.middleware', ['web']))->group(function () use ($prefix): void {
    Route::get($prefix.'-{randomHash}/{adsKey}', AdClickController::class)
        ->where('randomHash', '[a-f0-9]{40}')
        ->where('adsKey', '[A-Za-z0-9\-_]+')
        ->name('adment.click');

    Route::get('ads-click/{key}', [AdClickController::class, 'legacy'])
        ->where('key', '[A-Za-z0-9\-_]+')
        ->name('adment.click.legacy');
});
