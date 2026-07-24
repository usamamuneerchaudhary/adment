@if ($ads->isNotEmpty())
    <div @foreach ($attributes as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach>
    @foreach ($ads as $ad)
        @if ($ad->isAdsense())
            @if ($ad->google_adsense_slot_id && $adsenseClientId)
                @include('adment::partials.adsense-slot', [
                    'clientId' => $adsenseClientId,
                    'slotId' => $ad->google_adsense_slot_id,
                ])
            @endif
        @elseif ($ad->hasCreative())
            <div
                @if ($ad->impression_url)
                    data-adment-impression="{{ $ad->impression_url }}"
                    data-adment-key="{{ $ad->key }}"
                @endif
            >
                @include('adment::partials.custom-ad', ['ad' => $ad])
            </div>
        @endif
    @endforeach
    </div>
    @once('adment-impression-beacon')
        <script>
            (function () {
                if (window.__admentImpressionBeacon) {
                    return;
                }
                window.__admentImpressionBeacon = true;

                var csrf = document.querySelector('meta[name="csrf-token"]');
                var token = csrf ? csrf.getAttribute('content') : null;

                function track(el) {
                    var url = el.getAttribute('data-adment-impression');
                    if (!url || el.getAttribute('data-adment-tracked') === '1') {
                        return;
                    }
                    el.setAttribute('data-adment-tracked', '1');

                    var headers = {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    };
                    if (token) {
                        headers['X-CSRF-TOKEN'] = token;
                    }

                    if (navigator.sendBeacon) {
                        try {
                            var blob = new Blob([], { type: 'application/x-www-form-urlencoded' });
                            if (token) {
                                blob = new Blob(['_token=' + encodeURIComponent(token)], { type: 'application/x-www-form-urlencoded' });
                            }
                            navigator.sendBeacon(url, blob);
                            return;
                        } catch (e) {}
                    }

                    fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: headers,
                        keepalive: true
                    }).catch(function () {});
                }

                function observe() {
                    var nodes = document.querySelectorAll('[data-adment-impression]:not([data-adment-tracked="1"])');
                    if (!nodes.length) {
                        return;
                    }

                    if (!('IntersectionObserver' in window)) {
                        nodes.forEach(track);
                        return;
                    }

                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                                track(entry.target);
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: [0.5] });

                    nodes.forEach(function (node) {
                        observer.observe(node);
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', observe);
                } else {
                    observe();
                }
            })();
        </script>
    @endonce
@endif
