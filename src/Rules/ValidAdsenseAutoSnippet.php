<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts only the official AdSense Auto Ads snippet:
 * a single, empty, async <script> whose src points at
 * pagead2.googlesyndication.com/.../adsbygoogle.js with a
 * client=ca-pub-{16 digits} query parameter — and nothing else.
 */
class ValidAdsenseAutoSnippet implements ValidationRule
{
    /** Validate that the value is a safe official AdSense Auto Ads snippet. */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a string.'));

            return;
        }

        $snippet = trim($value);

        if (mb_strlen($snippet) > 1000) {
            $fail(__('The :attribute may not be longer than 1000 characters.'));

            return;
        }

        foreach (['eval(', 'document.write', 'javascript:', 'data:text', 'base64,'] as $forbidden) {
            if (stripos($snippet, $forbidden) !== false) {
                $fail(__('The :attribute contains disallowed JavaScript.'));

                return;
            }
        }

        // Exactly one <script> tag, with an empty body.
        if (preg_match_all('/<script\b/i', $snippet) !== 1
            || ! preg_match('/^<script\b([^>]*)>\s*<\/script>$/is', $snippet, $matches)) {
            $fail(__('The :attribute must be a single empty <script> tag.'));

            return;
        }

        $attributes = $matches[1];

        if (! preg_match('/\basync\b/i', $attributes)) {
            $fail(__('The :attribute must include the async attribute.'));

            return;
        }

        if (! preg_match('/\bcrossorigin=["\']anonymous["\']/i', $attributes)) {
            $fail(__('The :attribute must include crossorigin="anonymous".'));

            return;
        }

        if (! preg_match('/\bsrc=["\']([^"\']+)["\']/i', $attributes, $srcMatch)) {
            $fail(__('The :attribute must include a src attribute.'));

            return;
        }

        $src = $srcMatch[1];
        $parsed = parse_url($src);

        $validHost = ($parsed['host'] ?? null) === 'pagead2.googlesyndication.com';
        $validPath = str_ends_with($parsed['path'] ?? '', '/adsbygoogle.js');
        $validScheme = ($parsed['scheme'] ?? 'https') === 'https';

        parse_str($parsed['query'] ?? '', $query);
        $client = $query['client'] ?? null;
        $validClient = is_string($client) && preg_match('/^ca-pub-\d{16}$/', $client) === 1;

        if (! $validHost || ! $validPath || ! $validScheme || ! $validClient) {
            $fail(__('The :attribute must load adsbygoogle.js from pagead2.googlesyndication.com with a valid ca-pub client ID.'));
        }
    }
}
