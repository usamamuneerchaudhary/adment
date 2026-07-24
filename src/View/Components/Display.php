<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use Usamamuneerchaudhary\Adment\Contracts\ManagesAds;

/**
 * <x-adment::display location="sidebar" />
 * <x-adment::display location="sidebar" :single="false" />
 * <x-adment::display ad-key="HOMEPAGEBNNR" />
 */
class Display extends Component
{
    /** Create a display component for a location or a specific ad key. */
    public function __construct(
        protected ManagesAds $ads,
        public ?string $location = null,
        public ?string $adKey = null,
        public bool $single = true,
    ) {}

    /** Render the resolved ad HTML into the display Blade view. */
    public function render(): Closure
    {
        return function (array $data): View {
            /** @var ComponentAttributeBag $attributes */
            $attributes = $data['attributes'] ?? new ComponentAttributeBag([]);

            $extra = collect($attributes->getAttributes())
                ->except(['location', 'ad-key', 'adKey', 'single'])
                ->map(fn ($value): string => (string) $value)
                ->all();

            $html = $this->adKey !== null
                ? ($this->ads->displayAds($this->adKey, $extra) ?? '')
                : ($this->location !== null ? $this->ads->display($this->location, $extra, $this->single) : '');

            return $this->view('adment::components.display', [
                'html' => new HtmlString($html),
            ]);
        };
    }
}
