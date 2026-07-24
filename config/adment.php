<?php

declare(strict_types=1);
use Usamamuneerchaudhary\Adment\Models\Ad;

return [
    /*
    |--------------------------------------------------------------------------
    | Ads table name
    |--------------------------------------------------------------------------
    */
    'table' => 'ads',

    /*
    |--------------------------------------------------------------------------
    | Settings table name (key/value store for AdSense settings)
    |--------------------------------------------------------------------------
    */
    'settings_table' => 'ads_settings',

    /*
    |--------------------------------------------------------------------------
    | Registered ad locations
    |--------------------------------------------------------------------------
    | Seed locations here; applications and themes may also register locations
    | at runtime via Ads::registerLocation('sidebar', 'Sidebar').
    */
    'locations' => [
        'not_set' => 'Not set',
        // 'homepage-banner' => 'Homepage banner',
        // 'sidebar' => 'Sidebar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */
    'media' => [
        'disk' => env('FILAMENT_ADS_DISK', 'public'),
        'directory' => 'ads',
    ],

    /*
    |--------------------------------------------------------------------------
    | Click-tracking routes
    |--------------------------------------------------------------------------
    | The obfuscated route renders as /{prefix}-{sha1(key.id)}/{key} so that
    | the destination URL never appears in markup and naive ad blockers do
    | not match on "ads" in the path.
    */
    'routes' => [
        'enabled' => true,
        'click_prefix' => 'ac',
        'middleware' => ['web'],

        // Where to send visitors when an ad or its URL cannot be resolved.
        'fallback_redirect' => '/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public JSON API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'enabled' => false,
        'prefix' => 'api/v1',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model overrides
    |--------------------------------------------------------------------------
    | Swap in your own model (must extend the package model) to add columns,
    | relations, or multi-tenancy scopes.
    */
    'models' => [
        'ad' => Ad::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin panel
    |--------------------------------------------------------------------------
    */
    'panel' => [
        'navigation_group' => 'Marketing',
        'navigation_sort' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'order_min' => 0,
        'order_max' => 127,
    ],
];
