<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Contracts;

use Illuminate\Support\Collection;
use Usamamuneerchaudhary\Adment\Models\Ad;

interface ManagesAds
{
    /** Register a named placement location for ads. */
    public function registerLocation(string $key, string $name): static;

    /**
     * Return all registered location keys mapped to display names.
     *
     * @return array<string, string>
     */
    public function getLocations(): array;

    /** Load ads from the database into the in-memory collection. */
    public function load(bool $force = false): static;

    /**
     * Render displayable ads for a location (one random ad when $single is true).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function display(string $location, array $attributes = [], bool $single = true): string;

    /**
     * Render a single ad by its public key.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function displayAds(?string $key, array $attributes = []): ?string;

    /** Check whether a location has at least one displayable ad. */
    public function locationHasAds(string $location): bool;

    /** Find an ad by public key when it has an image creative. */
    public function getAd(string $key): ?Ad;

    /**
     * Return the loaded ads collection, optionally filtered to displayable ones.
     *
     * @return Collection<int, Ad>
     */
    public function getData(bool $load = false, bool $displayableOnly = false): Collection;
}
