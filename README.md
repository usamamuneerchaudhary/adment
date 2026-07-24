# Adment - Filament Ad Manager
![adment-1280 (4).png](public/images/screenshots/adment-1280%20%284%29.png)
A simple custom ad & Google AdSense manager for **Filament v5** on **Laravel 12/13** — placements, responsive (and GIF/video) creatives, weighted A/B rotation, scheduling windows, geo/device targeting, impression & CTR analytics, obfuscated click tracking, AdSense Auto Ads & ad units, `ads.txt` management, and an optional public JSON API.

> **Requires:** PHP 8.3+, Laravel 12 or 13, Filament 5 (Livewire 4).

---

## Features

- **Ad placements (locations):** register named slots in config or at runtime; drop ads into any Blade view.
- **Weighted A/B rotation:** when showing a single ad per location, selection is weighted by the ad's **Weight** (`order` column). Higher weight = more likely to be shown.
- **Scheduling windows:** optional `starts_at` and `expired_at` so custom ads only serve inside a time window.
- **Geo & device targeting:** restrict custom ads to ISO country codes (via CDN headers) and/or desktop / tablet / mobile.
- **Media types:** image, GIF, or video creatives for custom ads (desktop / tablet / mobile variants with fallbacks).
- **Impression tracking & CTR:** client viewport beacon (`IntersectionObserver`) records impressions; lifetime counters + daily stats power CTR dashboards.
- **Click tracking:** obfuscated `/ac-{sha1}/{key}` route increments a counter (without touching `updated_at`) and 302s to the destination. The raw destination URL never appears in your markup.
- **Analytics dashboard:** Filament page with impressions/clicks/CTR stats, a daily performance chart, and a top-ads-by-CTR table.
- **Google AdSense:** Auto Ads snippet injection (with a strict, security-hardened snippet validator) or per-unit slot rendering via a global publisher client ID.
- **`ads.txt` management** from the admin panel.
- **Filament resource:** full CRUD with conditional fields per ad type, filters, impressions, CTR, schedule highlighting.
- **Events:** `AdsLoading`, `AdClicked`, and `AdImpressionRecorded` for analytics/extension packages.
- **Optional public API:** `GET/POST /api/v1/ads` with key filtering.
- **Octane-safe:** services are container-scoped, not singletons.

## Installation

```bash
composer require usamamuneerchaudhary/adment

php artisan vendor:publish --tag=adment-migrations
php artisan migrate

# optional
php artisan vendor:publish --tag=adment-config
php artisan vendor:publish --tag=adment-views
```

Register the plugin in your panel provider:

```php
use Usamamuneerchaudhary\Adment\AdmentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(AdmentPlugin::make());
        // ->plugin(AdmentPlugin::make()->settingsPage(false)) to hide settings
        // ->plugin(AdmentPlugin::make()->analyticsPage(false)) to hide analytics
}
```

## Registering locations

In `config/adment.php`:

```php
'locations' => [
    'not_set' => 'Not set',
    'homepage-banner' => 'Homepage banner',
    'sidebar' => 'Sidebar',
],
```

Or at runtime (e.g. in a service provider or theme boot):

```php
use Usamamuneerchaudhary\Adment\Facades\Ads;

Ads::registerLocation('footer', 'Footer')
    ->registerLocation('post-content', 'Below post content');
```

## Displaying ads

**Blade components** (recommended):

```blade
{{-- One weighted-random published, in-window, targeted ad from a location --}}
<x-adment::display location="sidebar" />

{{-- All matching ads in a location, ordered by weight --}}
<x-adment::display location="sidebar" :single="false" />

{{-- A specific ad by key, with passthrough attributes --}}
<x-adment::display ad-key="HOMEPAGEBNNR" class="my-4" />
```

**Facade / service:**

```php
Ads::display('sidebar');                 // string of HTML
Ads::displayAds('HOMEPAGEBNNR');         // ?string
Ads::locationHasAds('sidebar');          // bool — for conditional layouts
Ads::getAd('HOMEPAGEBNNR');              // ?Ad model
```

Ads are loaded **once per request** and memoized; `Ads::load(force: true)` refreshes.

For impression beacons to work on public pages, include a CSRF meta tag in your layout:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

## Weighted rotation & scheduling

- **Weight** — the Filament **Weight** field maps to the `order` column. Values are treated as a minimum of `1` when picking a single ad for a location.
- **Starts at** — leave empty to start immediately; future values keep the ad out of rotation until then.
- **Ends at** — required for custom ads; past values mark the ad expired.

AdSense units ignore schedule dates and never expire.

## Geo & device targeting

On each custom ad you can set:

- **Countries** — ISO-3166 alpha-2 tags (e.g. `US`, `GB`). Empty = all countries.
- **Devices** — desktop / tablet / mobile checkboxes. Empty = all devices.

Country is resolved from request headers, in order:

1. Configured `adment.targeting.country_resolver` callable (if set)
2. `CF-IPCountry`
3. `CloudFront-Viewer-Country`
4. `X-Country-Code`

When an ad has country targeting and the country cannot be resolved, the ad is **excluded** by default (`unknown_country_behavior` = `exclude_restricted`).

```php
// config/adment.php
'targeting' => [
    'country_resolver' => fn (\Illuminate\Http\Request $request): ?string => /* ... */,
    'unknown_country_behavior' => 'exclude_restricted',
],
```

## Media types

Custom ads support:

| Media type | Rendered as |
|------------|-------------|
| Image | Responsive `<picture>` (desktop / tablet / mobile) |
| GIF | `<img>` with `srcset` fallbacks |
| Video | `<video autoplay muted loop playsinline>` (mp4 / webm) |

Upload accepted types are enforced in Filament based on the selected media type.

## Impression tracking & CTR

Custom creatives wrap in a beacon container. When ~50% of the ad is visible, a one-shot `POST` to `/ai-{sha1}/{key}` records an impression (lifetime `impressions` + today's `ad_daily_stats` row) and dispatches `AdImpressionRecorded`.

CTR is `clicks / impressions × 100`. Open **Ad analytics** in the Marketing nav group for:

- Period totals (impressions, clicks, CTR, active ads)
- Daily impressions & clicks chart
- Top ads by CTR (respects `analytics.min_impressions_for_ctr_ranking`)

## AdSense

Configure in **Ads settings** in the panel (mode: Disabled / Auto Ads / Ad units), then add to your public layout:

```blade
<head>
    ...
    <x-adment::adsense-head />
</head>
<body>
    ...
    <x-adment::adsense-foot />
</body>
```

- **Auto Ads:** paste the exact snippet from AdSense. It's validated hard: a single empty async `<script>` from `pagead2.googlesyndication.com` with a `ca-pub-{16 digits}` client — inline JS, `eval`, `document.write`, and data URLs are rejected.
- **Ad units:** set your `ca-pub-…` client ID, then create ads of type *Google AdSense unit* with a slot ID. Units render as `<ins class="adsbygoogle">` and never expire. Impressions for AdSense units are not tracked by Adment (AdSense handles its own metrics).

## Click tracking

Custom ads with a destination URL render links through `/ac-{sha1(key.id)}/{key}`. The handler verifies the hash (`hash_equals`), refuses non-HTTP(S) destinations, increments `clicked` and today's daily click stat without model events or timestamp bumps, dispatches `AdClicked`, and redirects. A legacy `/ads-click/{key}` route is included.

## Public API (optional)

Enable in config (`'api' => ['enabled' => true]`):

```
GET /api/v1/ads
GET /api/v1/ads?keys[]=HOMEPAGEBNNR&keys[]=SIDEBAR00001
```

Returns published, in-window ads ordered by weight, with tracked `link` URLs; the raw destination is never exposed.

## Extending

- **Custom model:** point `adment.models.ad` at your subclass (add tenancy scopes, relations, etc.).
- **Analytics:** listen to `AdClicked`, `AdImpressionRecorded` (ad, referer, user agent), and `AdsLoading`.
- **Rendering:** publish and override the views, or swap the whole `ManagesAds` binding.
- **Country detection:** set `adment.targeting.country_resolver` to a callable that returns an ISO country code.

## Architecture

```
Blade component / Facade
        │
        ▼
AdsManager (scoped service, per-request memoized)
        │  filterDisplayable(): published + schedule window
        │  filterTargeted(): country + device rules
        │  WeightedAdSelector (when single=true)
        ▼
partials/ad-display.blade.php
   ├── adsense-slot.blade.php   (<ins class="adsbygoogle">)
   └── custom-ad.blade.php      (image / gif / video + tracked click URL)
        └── viewport beacon ── POST /ai-… ── AdImpressionController

AdClickController ── hash check ── recordClick() ── AdClicked ── redirect()->away()
AdAnalyticsRecorder ── ads.impressions/clicked + ad_daily_stats
AdsSettings ── key/value table + forever-cache ── ManageAdsSettings (Filament page)
AdAnalyticsDashboard ── stats / chart / top CTR widgets
```

Design decisions:

- **Contract-first:** everything binds `ManagesAds`, so a Pro/analytics package can decorate or replace the manager without touching consumers.
- **Scoped, not singleton**, for Octane and queue-worker safety.
- **No repository layer:** Eloquent scopes (`displayable()`) are the query API; the manager consumes collections so filtering logic exists exactly once in the model.
- **Viewport beacons over render-time increments** so CTR reflects ads users actually saw.
- **CDN headers for geo** by default (zero new dependencies); swap in MaxMind or similar via `country_resolver`.
- **Settings are dependency-free** (key/value table + cache); swap for `spatie/laravel-settings` if your app standardizes on it.

## Testing

```bash
composer test        # Pest
composer analyse     # PHPStan (larastan, level 6)
composer format      # Pint
```

The suite covers the model (key generation, hash, image fallbacks, scopes, schedule/CTR, click & impression counting), the manager (filtering, weighted selection, targeting, attribute passthrough, AdSense rendering, URL non-leakage), click & impression routes (hash tampering, unsafe URLs, legacy route, daily stats), Blade components (image / GIF / video), targeting & weighted selector units, the public API, the AdSense snippet validator, and the Filament resource + settings page via Livewire.

## Screenshots 
![Screenshot 2026-07-24 at 21.33.29.png](public/images/screenshots/Screenshot%202026-07-24%20at%2021.33.29.png)
![Screenshot 2026-07-24 at 21.33.37.png](public/images/screenshots/Screenshot%202026-07-24%20at%2021.33.37.png)
![Screenshot 2026-07-24 at 21.33.50.png](public/images/screenshots/Screenshot%202026-07-24%20at%2021.33.50.png)
![Screenshot 2026-07-25 at 02.15.51.png](public/images/screenshots/Screenshot%202026-07-25%20at%2002.15.51.png)
## License
[MIT](LICENSE.md)
