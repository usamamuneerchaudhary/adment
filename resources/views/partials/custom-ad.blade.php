@if ($ad->click_url)
    <a href="{{ $ad->click_url }}" @if ($ad->open_in_new_tab) target="_blank" rel="nofollow noopener sponsored" @endif>
@endif
        <picture>
            <source media="(min-width: 1200px)" srcset="{{ $ad->image_url }}">
            <source media="(min-width: 768px)" srcset="{{ $ad->tablet_image_url }}">
            <source media="(max-width: 767px)" srcset="{{ $ad->mobile_image_url }}">
            <img src="{{ $ad->image_url }}" alt="{{ $ad->name }}" loading="lazy" decoding="async">
        </picture>
@if ($ad->click_url)
    </a>
@endif
