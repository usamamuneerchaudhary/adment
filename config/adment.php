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
    | Daily stats table name
    |--------------------------------------------------------------------------
    */
    'daily_stats_table' => 'ad_daily_stats',

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
        'disk' => env('ADMENT_DISK', 'public'),
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
        'impression_prefix' => 'ai',
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

    /*
    |--------------------------------------------------------------------------
    | Targeting
    |--------------------------------------------------------------------------
    | Country is resolved from CDN headers (CF-IPCountry, CloudFront-Viewer-
    | Country, X-Country-Code) or a custom country_resolver callable.
    | When the country is unknown and an ad has geo targeting, that ad is
    | excluded (exclude_restricted).
    */
    'targeting' => [
        'country_resolver' => null,
        'unknown_country_behavior' => 'exclude_restricted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'min_impressions_for_ctr_ranking' => 100,
    ],
];
