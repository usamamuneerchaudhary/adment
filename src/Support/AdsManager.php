<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Support;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Usamamuneerchaudhary\Adment\Contracts\ManagesAds;
use Usamamuneerchaudhary\Adment\Events\AdsLoading;
use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;

class AdsManager implements ManagesAds
{
    /** @var Collection<int, Ad> */
    protected Collection $data;

    protected bool $loaded = false;

    /** @var array<string, string> */
    protected array $locations;

    /** Create a new ads manager with view rendering and settings access. */
    public function __construct(
        protected ViewFactory $view,
        protected AdsSettings $settings,
        protected AdTargeting $targeting = new AdTargeting,
        protected WeightedAdSelector $weightedSelector = new WeightedAdSelector,
    ) {
        $this->data = new Collection;
        /** @var array<string, string> $locations */
        $locations = (array) config('adment.locations', ['not_set' => 'Not set']);
        $this->locations = $locations;
    }

    /** Register a named placement location for ads. */
    public function registerLocation(string $key, string $name): static
    {
        $this->locations[$key] = $name;

        return $this;
    }

    /**
     * Return all registered location keys mapped to display names.
     *
     * @return array<string, string>
     */
    public function getLocations(): array
    {
        return $this->locations + ['not_set' => __('Not set')];
    }

    /** Load ads from the database into the in-memory collection. */
    public function load(bool $force = false): static
    {
        if ($this->loaded && ! $force) {
            return $this;
        }

        /** @var class-string<Ad> $model */
        $model = config('adment.models.ad', Ad::class);

        $this->data = $model::query()->get();
        $this->loaded = true;

        AdsLoading::dispatch($this->data);

        return $this;
    }

    /**
     * Keep only ads that are currently displayable.
     *
     * @param  Collection<int, Ad>  $ads
     * @return Collection<int, Ad>
     */
    public function filterDisplayable(Collection $ads): Collection
    {
        return $ads->filter(fn (Ad $ad): bool => $ad->isDisplayable());
    }

    /**
     * Keep only ads that match the current request's targeting rules.
     *
     * @param  Collection<int, Ad>  $ads
     * @return Collection<int, Ad>
     */
    public function filterTargeted(Collection $ads, ?Request $request = null): Collection
    {
        $request ??= request();

        return $ads->filter(fn (Ad $ad): bool => $this->targeting->matches($ad, $request));
    }

    /**
     * Render displayable ads for a location (one weighted-random ad when $single is true).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function display(string $location, array $attributes = [], bool $single = true): string
    {
        $this->load();

        $ads = $this->filterTargeted(
            $this->filterDisplayable($this->data)
                ->where('location', $location)
                ->sortBy('order')
                ->values(),
        )->values();

        if ($ads->isEmpty()) {
            return '';
        }

        if ($single) {
            $selected = $this->weightedSelector->select($ads);

            if (! $selected instanceof Ad) {
                return '';
            }

            $ads = new Collection([$selected]);
        }

        return $this->render($ads, $attributes);
    }

    /**
     * Render a single ad by its public key.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function displayAds(?string $key, array $attributes = []): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $this->load();

        $ad = $this->filterTargeted($this->filterDisplayable($this->data))
            ->firstWhere('key', $key);

        if (! $ad instanceof Ad) {
            return null;
        }

        $attributes['style'] ??= 'text-align: center;';

        return $this->render(new Collection([$ad]), $attributes);
    }

    /** Check whether a location has at least one displayable ad. */
    public function locationHasAds(string $location): bool
    {
        $this->load();

        return $this->filterTargeted(
            $this->filterDisplayable($this->data)->where('location', $location),
        )->isNotEmpty();
    }

    /** Find an ad by public key when it has a creative. */
    public function getAd(string $key): ?Ad
    {
        $this->load();

        $ad = $this->data->firstWhere('key', $key);

        return $ad instanceof Ad && $ad->hasCreative() && ! $ad->isAdsense() ? $ad : null;
    }

    /**
     * Return the loaded ads collection, optionally filtered to displayable ones.
     *
     * @return Collection<int, Ad>
     */
    public function getData(bool $load = false, bool $displayableOnly = false): Collection
    {
        if ($load) {
            $this->load();
        }

        return $displayableOnly ? $this->filterDisplayable($this->data)->values() : $this->data;
    }

    /**
     * Render the given ads through the shared Blade partial.
     *
     * @param  Collection<int, Ad>  $ads
     * @param  array<string, mixed>  $attributes
     */
    protected function render(Collection $ads, array $attributes = []): string
    {
        return $this->view
            ->make('adment::partials.ad-display', [
                'ads' => $ads,
                'attributes' => $attributes,
                'adsenseClientId' => $this->settings->unitClientId(),
            ])
            ->render();
    }
}
