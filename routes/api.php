<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Usamamuneerchaudhary\Adment\Http\Controllers\Api\AdController;

Route::middleware(config('adment.api.middleware', ['api']))
    ->prefix(config('adment.api.prefix', 'api/v1'))
    ->group(function (): void {
        Route::match(['get', 'post'], 'ads', [AdController::class, 'index'])
            ->name('adment.api.index');
    });
