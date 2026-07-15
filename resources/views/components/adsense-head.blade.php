@if ($mode === \Usamamuneerchaudhary\Adment\Enums\AdsenseMode::Auto && $autoSnippet)
    {!! $autoSnippet !!}
@elseif ($mode === \Usamamuneerchaudhary\Adment\Enums\AdsenseMode::Unit && $clientId)
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $clientId }}"
            crossorigin="anonymous"></script>
@endif
