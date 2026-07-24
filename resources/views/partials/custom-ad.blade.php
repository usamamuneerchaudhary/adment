@if ($ad->click_url)
    <a href="{{ $ad->click_url }}" @if ($ad->open_in_new_tab) target="_blank" rel="nofollow noopener sponsored" @endif>
@endif
    @if ($ad->media_type === \Usamamuneerchaudhary\Adment\Enums\AdMediaType::Video)
        <video
            autoplay
            muted
            loop
            playsinline
            style="max-width: 100%; height: auto; display: block;"
        >
            <source src="{{ $ad->image_url }}" type="{{ str_ends_with((string) $ad->image, '.webm') ? 'video/webm' : 'video/mp4' }}">
        </video>
    @elseif ($ad->media_type === \Usamamuneerchaudhary\Adment\Enums\AdMediaType::Gif)
        <img
            src="{{ $ad->image_url }}"
            srcset="{{ collect([
                $ad->mobile_image_url ? $ad->mobile_image_url.' 767w' : null,
                $ad->tablet_image_url ? $ad->tablet_image_url.' 1199w' : null,
                $ad->image_url ? $ad->image_url.' 1200w' : null,
            ])->filter()->implode(', ') }}"
            sizes="(max-width: 767px) 100vw, (max-width: 1199px) 100vw, 1200px"
            alt="{{ $ad->name }}"
            loading="lazy"
            decoding="async"
            style="max-width: 100%; height: auto; display: block;"
        >
    @else
        <picture>
            <source media="(min-width: 1200px)" srcset="{{ $ad->image_url }}">
            <source media="(min-width: 768px)" srcset="{{ $ad->tablet_image_url }}">
            <source media="(max-width: 767px)" srcset="{{ $ad->mobile_image_url }}">
            <img src="{{ $ad->image_url }}" alt="{{ $ad->name }}" loading="lazy" decoding="async">
        </picture>
    @endif
@if ($ad->click_url)
    </a>
@endif
