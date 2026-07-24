<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;

/**
 * Place before </body>: <x-adment::adsense-foot />
 */
class AdsenseFoot extends Component
{
    /** Create the AdSense footer-injection component. */
    public function __construct(protected AdsSettings $settings) {}

    /** Render unit-mode AdSense scripts before the closing body tag. */
    public function render(): View
    {
        return view('adment::components.adsense-foot', [
            'mode' => $this->settings->mode(),
            'clientId' => $this->settings->unitClientId(),
        ]);
    }
}
