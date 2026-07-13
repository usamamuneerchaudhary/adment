# Filament Ads

A production-grade ad & Google AdSense manager for **Filament v5** on **Laravel 12/13** — ad placements ("locations"), responsive creatives, obfuscated click tracking, AdSense Auto Ads & ad units, `ads.txt` management, and an optional public JSON API.

Ported and re-architected from the battle-tested Botble Ads plugin, rebuilt idiomatically for the Filament ecosystem.

> **Requires:** PHP 8.3+, Laravel 12 or 13, Filament 5 (Livewire 4).

---

## Features

- **Ad placements (locations):** register named slots in config or at runtime; drop ads into any Blade view.
- **Responsive creatives:** desktop / tablet / mobile images with automatic fallback chain.
- **Click tracking:** obfuscated `/ac-{sha1}/{key}` route increments a counter (without touching `updated_at`) and 302s to the destination. The raw destination URL never appears in your markup.
- **Google AdSense:** Auto Ads snippet injection (with a strict, security-hardened snippet validator) or per-unit slot rendering via a global publisher client ID.
- **`ads.txt` management** from the admin panel.
- **Filament resource:** full CRUD with conditional fields per ad type, filters, click counts, and expiry highlighting.
- **Events:** `AdsLoading` and `AdClicked` for analytics/extension packages.
- **Optional public API:** `GET/POST /api/v1/ads` with key filtering.
- **Octane-safe:** services are container-scoped, not singletons.

## Installation

```bash
composer require usamamuneerchaudhary/filads

php artisan vendor:publish --tag=filads-migrations
php artisan migrate

# optional
php artisan vendor:publish --tag=filads-config
php artisan vendor:publish --tag=filads-views
```

Register the plugin in your panel provider:

```php
use Usamamuneerchaudhary\FilAds\FilAdsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilAdsPlugin::make());
        // ->plugin(FilAdsPlugin::make()->settingsPage(false)) to hide settings
}
```

## Registering locations

In `config/filads.php`:

```php
'locations' => [
    'not_set' => 'Not set',
    'homepage-banner' => 'Homepage banner',
    'sidebar' => 'Sidebar',
],
```

Or at runtime (e.g. in a service provider or theme boot):

```php
use Usamamuneerchaudhary\FilAds\Facades\Ads;

Ads::registerLocation('footer', 'Footer')
    ->registerLocation('post-content', 'Below post content');
```

## Displaying ads

**Blade components** (recommended):

```blade
{{-- One random published, non-expired ad from a location --}}
<x-filads::display location="sidebar" />

{{-- All ads in a location, ordered by weight --}}
<x-filads::display location="sidebar" :single="false" />

{{-- A specific ad by key, with passthrough attributes --}}
<x-filads::display ad-key="HOMEPAGEBNNR" class="my-4" />
```

**Facade / service:**

```php
Ads::display('sidebar');                 // string of HTML
Ads::displayAds('HOMEPAGEBNNR');         // ?string
Ads::locationHasAds('sidebar');          // bool — for conditional layouts
Ads::getAd('HOMEPAGEBNNR');              // ?Ad model
```

Ads are loaded **once per request** and memoized; `Ads::load(force: true)` refreshes.

## AdSense

Configure in **Ads settings** in the panel (mode: Disabled / Auto Ads / Ad units), then add to your public layout:

```blade
<head>
    ...
    <x-filads::adsense-head />
</head>
<body>
    ...
    <x-filads::adsense-foot />
</body>
```

- **Auto Ads:** paste the exact snippet from AdSense. It's validated hard: a single empty async `<script>` from `pagead2.googlesyndication.com` with a `ca-pub-{16 digits}` client — inline JS, `eval`, `document.write`, and data URLs are rejected.
- **Ad units:** set your `ca-pub-…` client ID, then create ads of type *Google AdSense unit* with a slot ID. Units render as `<ins class="adsbygoogle">` and never expire.

## Click tracking

Custom ads with a destination URL render links through `/ac-{sha1(key.id)}/{key}`. The handler verifies the hash (`hash_equals`), refuses non-HTTP(S) destinations, increments `clicked` without model events or timestamp bumps, dispatches `AdClicked`, and redirects. A legacy `/ads-click/{key}` route is included for parity with Botble.

## Public API (optional)

Enable in config (`'api' => ['enabled' => true]`):

```
GET /api/v1/ads
GET /api/v1/ads?keys[]=HOMEPAGEBNNR&keys[]=SIDEBAR00001
```

Returns published, non-expired ads ordered by weight, with tracked `link` URLs — the raw destination is never exposed.

## Extending

- **Custom model:** point `filads.models.ad` at your subclass (add tenancy scopes, relations, etc.).
- **Analytics:** listen to `AdClicked` (ad, referer, user agent) and `AdsLoading`.
- **Rendering:** publish and override the views, or swap the whole `ManagesAds` binding.

## Architecture

```
Blade component / Facade
        │
        ▼
AdsManager (scoped service, per-request memoized)
        │  filterDisplayable(): published + (adsense OR not expired)
        ▼
partials/ad-display.blade.php
   ├── adsense-slot.blade.php   (<ins class="adsbygoogle">)
   └── custom-ad.blade.php      (<picture> + tracked click URL)

AdClickController ── hash check ── recordClick() ── AdClicked ── redirect()->away()
AdsSettings ── key/value table + forever-cache ── ManageAdsSettings (Filament page)
```

Design decisions:

- **Contract-first:** everything binds `ManagesAds`, so a Pro/analytics package can decorate or replace the manager without touching consumers.
- **Scoped, not singleton**, for Octane and queue-worker safety.
- **No repository layer:** Eloquent scopes (`displayable()`) are the query API; the manager consumes collections so filtering logic exists exactly once in the model.
- **Settings are dependency-free** (key/value table + cache); swap for `spatie/laravel-settings` if your app standardizes on it.

## Testing

```bash
composer test        # Pest
composer analyse     # PHPStan (larastan, level 6)
composer format      # Pint
```

The suite covers the model (key generation, hash, image fallbacks, scopes, click counting), the manager (filtering, random single, ordering, attribute passthrough, AdSense rendering, URL non-leakage), click routes (hash tampering, unsafe URLs, legacy route), Blade components, the public API, the AdSense snippet validator (11 attack/malformation cases), and the Filament resource + settings page via Livewire.

## Roadmap (Pro tier)

Impression tracking & CTR dashboards · weighted A/B rotation · scheduling windows · geo/device targeting · Google Ad Manager support. The free core stays MIT forever.

## License

MIT. Rename the `Usamamuneerchaudhary` namespace and `usamamuneerchaudhary/filads` package name before publishing.
