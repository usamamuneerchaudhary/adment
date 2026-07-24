<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Settings;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;
use Usamamuneerchaudhary\Adment\Enums\AdsenseMode;

class AdsSettings
{
    protected const string CACHE_KEY = 'adment.settings';

    public const string KEY_MODE = 'adsense_mode';

    public const string KEY_AUTO_SNIPPET = 'adsense_auto_ads_snippet';

    public const string KEY_UNIT_CLIENT_ID = 'adsense_unit_client_id';

    /** Create a settings store backed by the database and cache. */
    public function __construct(
        protected ConnectionInterface $db,
        protected Cache $cache,
    ) {}

    /** Return the configured AdSense injection mode. */
    public function mode(): AdsenseMode
    {
        return AdsenseMode::tryFrom((string) $this->get(self::KEY_MODE)) ?? AdsenseMode::None;
    }

    /** Return the Auto Ads script snippet when configured. */
    public function autoAdsSnippet(): ?string
    {
        $value = $this->get(self::KEY_AUTO_SNIPPET);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** Return the AdSense publisher client ID when configured. */
    public function unitClientId(): ?string
    {
        $value = $this->get(self::KEY_UNIT_CLIENT_ID);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** Persist AdSense mode and the matching snippet or client ID fields. */
    public function updateAdsense(AdsenseMode $mode, ?string $autoSnippet = null, ?string $unitClientId = null): void
    {
        $this->set(self::KEY_MODE, $mode->value);
        $this->set(self::KEY_AUTO_SNIPPET, $mode === AdsenseMode::Auto ? $autoSnippet : null);
        $this->set(self::KEY_UNIT_CLIENT_ID, $mode === AdsenseMode::Unit ? $unitClientId : null);
    }

    /** Get a single settings value by key. */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** Persist a settings value and flush the settings cache. */
    public function set(string $key, mixed $value): void
    {
        $this->db->table($this->table())->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()],
        );

        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * Return all settings as a key/value map, loaded from cache when possible.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->cache->rememberForever(self::CACHE_KEY, function (): array {
            return $this->db->table($this->table())
                ->pluck('value', 'key')
                ->map(fn ($value) => is_string($value) ? json_decode($value, true) : $value)
                ->all();
        });
    }

    /** Forget the cached settings payload. */
    public function flushCache(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /** Resolve the database table name used for settings rows. */
    protected function table(): string
    {
        return config('adment.settings_table', 'ads_settings');
    }
}
