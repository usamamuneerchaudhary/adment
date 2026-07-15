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
        @elseif ($ad->image)
            @include('adment::partials.custom-ad', ['ad' => $ad])
        @endif
    @endforeach
    </div>
@endif
