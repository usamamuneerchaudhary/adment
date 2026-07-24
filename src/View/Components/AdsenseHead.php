<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;

/**
 * Place inside <head>: <x-adment::adsense-head />
 */
class AdsenseHead extends Component
{
    /** Create the AdSense head-injection component. */
    public function __construct(protected AdsSettings $settings) {}

    /** Render Auto Ads or unit-mode scripts for the document head. */
    public function render(): View
    {
        return view('adment::components.adsense-head', [
            'mode' => $this->settings->mode(),
            'autoSnippet' => $this->settings->autoAdsSnippet(),
            'clientId' => $this->settings->unitClientId(),
        ]);
    }
}
